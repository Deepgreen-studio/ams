<?php

namespace App\Domains\Applications\Services;

use App\Domains\Applications\Events\ApplicationCreated;
use App\Domains\Applications\Events\ApplicationDeleted;
use App\Domains\Applications\Events\ApplicationRestored;
use App\Domains\Applications\Events\ApplicationUpdated;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Repositories\ApplicationRepository;
use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Integrations\Repositories\IntegrationRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApplicationService
{
    public function __construct(
        private readonly ApplicationRepository $applicationRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly IntegrationRepository $integrationRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $companyIdentifier = $filters['company'] ?? $filters['company_id'] ?? null;
        if (! empty($companyIdentifier) && ! is_numeric($companyIdentifier)) {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $companyIdentifier);
            $filters['company_id'] = $company->id;
        }

        $integrationIdentifier = $filters['integration'] ?? $filters['integration_id'] ?? null;
        if (! empty($integrationIdentifier) && ! is_numeric($integrationIdentifier)) {
            $integration = $this->integrationRepository->findByIdentifierOrFail((string) $integrationIdentifier);
            $filters['integration_id'] = $integration->id;
        }

        return $this->applicationRepository->paginateFiltered($filters);
    }

    public function find(string $identifier, bool $withTrashed = false): Application
    {
        return $this->applicationRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): Application
    {
        $application = $this->find($identifier);

        return $application->load([
            'company:id,uuid,company_name,status',
            'integration:id,uuid,name,slug,status,type',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Application
    {
        return DB::transaction(function () use ($data, $actor): Application {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
            $payload = $this->preparePayload($data);
            $payload['company_id'] = $company->id;
            $payload['integration_id'] = $this->resolveIntegrationId(
                $data['integration_id'] ?? null,
                $company->id
            );
            $payload['slug'] = $this->resolveUniqueSlug(
                $company->id,
                $payload['slug'] ?? null,
                $payload['name']
            );
            $payload['status'] = $payload['status'] ?? 'draft';
            $payload['visibility'] = $payload['visibility'] ?? 'private';
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            $application = $this->applicationRepository->createApplication($payload);
            event(new ApplicationCreated($application, $actor));

            return $application;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): Application
    {
        return DB::transaction(function () use ($identifier, $data, $actor): Application {
            $application = $this->applicationRepository->findByIdentifierOrFail($identifier);
            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('integration_id', $data)) {
                $payload['integration_id'] = $this->resolveIntegrationId(
                    $data['integration_id'],
                    $application->company_id
                );
            }

            if (array_key_exists('slug', $payload) || array_key_exists('name', $payload)) {
                $name = $payload['name'] ?? $application->name;
                $slugInput = $payload['slug'] ?? null;
                $payload['slug'] = $this->resolveUniqueSlug(
                    $application->company_id,
                    $slugInput,
                    $name,
                    $application->id
                );
            }

            $updated = $this->applicationRepository->updateApplication($application, $payload);
            event(new ApplicationUpdated($updated, $actor));

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $application = $this->applicationRepository->findByIdentifierOrFail($identifier);
            $this->applicationRepository->updateApplication($application, ['updated_by' => $actor->id]);
            $application->delete();
            event(new ApplicationDeleted($application, $actor));
        });
    }

    public function restore(string $identifier, User $actor): Application
    {
        return DB::transaction(function () use ($identifier, $actor): Application {
            $application = $this->applicationRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $application->trashed()) {
                throw new ApiException('Application is not deleted.', 422);
            }

            $application->restore();
            $restored = $this->applicationRepository->updateApplication($application, ['updated_by' => $actor->id]);
            event(new ApplicationRestored($restored, $actor));

            return $restored;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, bool $isUpdate = false): array
    {
        $allowed = [
            'name',
            'slug',
            'description',
            'platform',
            'category',
            'icon',
            'banner',
            'current_version',
            'minimum_supported_version',
            'status',
            'visibility',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        foreach ([
            'description',
            'slug',
            'category',
            'icon',
            'banner',
            'current_version',
            'minimum_supported_version',
        ] as $nullable) {
            if (array_key_exists($nullable, $payload) && blank($payload[$nullable])) {
                $payload[$nullable] = null;
            }
        }

        if ($isUpdate && array_key_exists('slug', $payload) && $payload['slug'] === null) {
            unset($payload['slug']);
        }

        return $payload;
    }

    protected function resolveIntegrationId(mixed $identifier, int $companyId): ?int
    {
        if ($identifier === null || $identifier === '') {
            return null;
        }

        $integration = $this->integrationRepository->findByIdentifierOrFail((string) $identifier);

        if ((int) $integration->company_id !== $companyId) {
            throw new ApiException('Integration does not belong to the selected company.', 422);
        }

        return $integration->id;
    }

    protected function resolveUniqueSlug(int $companyId, ?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name);
        if ($base === '') {
            $base = 'application';
        }

        $candidate = $base;
        $suffix = 2;

        while ($this->applicationRepository->slugExistsForCompany($companyId, $candidate, $ignoreId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}

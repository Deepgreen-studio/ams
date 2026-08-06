<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Integrations\Events\IntegrationCreated;
use App\Domains\Integrations\Events\IntegrationDeleted;
use App\Domains\Integrations\Events\IntegrationRestored;
use App\Domains\Integrations\Events\IntegrationUpdated;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Integrations\Repositories\IntegrationRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IntegrationService
{
    public function __construct(
        private readonly IntegrationRepository $integrationRepository,
        private readonly CompanyRepository $companyRepository
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

        return $this->integrationRepository->paginateFiltered($filters);
    }

    public function find(string $identifier, bool $withTrashed = false): Integration
    {
        return $this->integrationRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): Integration
    {
        $integration = $this->find($identifier);

        return $integration->load([
            'company:id,uuid,company_name,status',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Integration
    {
        return DB::transaction(function () use ($data, $actor): Integration {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
            $payload = $this->preparePayload($data);
            $payload['company_id'] = $company->id;
            $payload['slug'] = $this->resolveUniqueSlug(
                $company->id,
                $payload['slug'] ?? null,
                $payload['name']
            );
            $payload['status'] = $payload['status'] ?? 'draft';
            $payload['health_status'] = $payload['health_status'] ?? 'unknown';
            $payload['timeout'] = $payload['timeout'] ?? 30;
            $payload['retry_attempts'] = $payload['retry_attempts'] ?? 3;
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            $integration = $this->integrationRepository->createIntegration($payload);
            event(new IntegrationCreated($integration, $actor));

            return $integration;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): Integration
    {
        return DB::transaction(function () use ($identifier, $data, $actor): Integration {
            $integration = $this->integrationRepository->findByIdentifierOrFail($identifier);
            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('slug', $payload) || array_key_exists('name', $payload)) {
                $name = $payload['name'] ?? $integration->name;
                $slugInput = $payload['slug'] ?? null;
                $payload['slug'] = $this->resolveUniqueSlug(
                    $integration->company_id,
                    $slugInput,
                    $name,
                    $integration->id
                );
            }

            $updated = $this->integrationRepository->updateIntegration($integration, $payload);
            event(new IntegrationUpdated($updated, $actor));

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $integration = $this->integrationRepository->findByIdentifierOrFail($identifier);
            $this->integrationRepository->updateIntegration($integration, ['updated_by' => $actor->id]);
            $integration->delete();
            event(new IntegrationDeleted($integration, $actor));
        });
    }

    public function restore(string $identifier, User $actor): Integration
    {
        return DB::transaction(function () use ($identifier, $actor): Integration {
            $integration = $this->integrationRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $integration->trashed()) {
                throw new ApiException('Integration is not deleted.', 422);
            }

            $integration->restore();
            $restored = $this->integrationRepository->updateIntegration($integration, ['updated_by' => $actor->id]);
            event(new IntegrationRestored($restored, $actor));

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
            'type',
            'status',
            'authentication_type',
            'base_url',
            'api_version',
            'timeout',
            'retry_attempts',
            'health_status',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        foreach (['description', 'base_url', 'api_version', 'slug'] as $nullable) {
            if (array_key_exists($nullable, $payload) && blank($payload[$nullable])) {
                $payload[$nullable] = null;
            }
        }

        if (array_key_exists('timeout', $payload)) {
            $payload['timeout'] = (int) $payload['timeout'];
        }

        if (array_key_exists('retry_attempts', $payload)) {
            $payload['retry_attempts'] = (int) $payload['retry_attempts'];
        }

        if ($isUpdate && array_key_exists('slug', $payload) && $payload['slug'] === null) {
            unset($payload['slug']);
        }

        return $payload;
    }

    protected function resolveUniqueSlug(int $companyId, ?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name);
        if ($base === '') {
            $base = 'integration';
        }

        $candidate = $base;
        $suffix = 2;

        while ($this->integrationRepository->slugExistsForCompany($companyId, $candidate, $ignoreId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}

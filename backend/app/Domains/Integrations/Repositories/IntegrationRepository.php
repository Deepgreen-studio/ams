<?php

namespace App\Domains\Integrations\Repositories;

use App\Domains\Integrations\Models\Integration;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class IntegrationRepository extends BaseRepository
{
    public function __construct(Integration $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?Integration
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var Integration|null $integration */
        $integration = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $integration;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): Integration
    {
        $integration = $this->findByIdentifier($identifier, $withTrashed);

        if (! $integration) {
            abort(404, 'Integration not found.');
        }

        return $integration;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with([
                'company:id,uuid,company_name',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('base_url', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['authentication_type'])) {
            $query->where('authentication_type', $filters['authentication_type']);
        }

        if (! empty($filters['health_status'])) {
            $query->where('health_status', $filters['health_status']);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'name', 'slug', 'type', 'status', 'health_status', 'created_at', 'updated_at'];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createIntegration(array $data): Integration
    {
        /** @var Integration $integration */
        $integration = $this->model->newQuery()->create($data);

        return $integration->fresh(['company', 'creator', 'updater']) ?? $integration;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateIntegration(Integration $integration, array $data): Integration
    {
        $integration->fill($data);
        $integration->save();

        return $integration->refresh()->load(['company', 'creator', 'updater']);
    }

    public function slugExistsForCompany(int $companyId, string $slug, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('company_id', $companyId)
            ->where('slug', $slug)
            ->whereNull('deleted_at');

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}

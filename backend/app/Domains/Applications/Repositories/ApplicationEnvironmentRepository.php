<?php

namespace App\Domains\Applications\Repositories;

use App\Domains\Applications\Models\ApplicationEnvironment;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ApplicationEnvironmentRepository extends BaseRepository
{
    public function __construct(ApplicationEnvironment $model)
    {
        parent::__construct($model);
    }

    public function findForApplication(int $applicationId, string $identifier, bool $withTrashed = false): ApplicationEnvironment
    {
        $query = $this->model->newQuery()->where('application_id', $applicationId);

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var ApplicationEnvironment|null $environment */
        $environment = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        if (! $environment) {
            abort(404, 'Application environment not found.');
        }

        return $environment;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForApplication(int $applicationId, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($applicationId, $filters)
            ->with([
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, ApplicationEnvironment>
     */
    public function dashboardForApplication(int $applicationId): Collection
    {
        return $this->model->newQuery()
            ->where('application_id', $applicationId)
            ->with([
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ])
            ->orderByRaw("CASE type
                WHEN 'development' THEN 1
                WHEN 'testing' THEN 2
                WHEN 'staging' THEN 3
                WHEN 'sandbox' THEN 4
                WHEN 'production' THEN 5
                ELSE 99
            END")
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(int $applicationId, array $filters = []): Builder
    {
        $query = $this->model->newQuery()->where('application_id', $applicationId);

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('api_url', 'like', "%{$search}%")
                    ->orWhere('web_url', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['health_status'])) {
            $query->where('health_status', $filters['health_status']);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'type');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowed = ['name', 'slug', 'type', 'status', 'health_status', 'is_current', 'created_at', 'updated_at'];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'type';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    public function slugExists(int $applicationId, string $slug, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('application_id', $applicationId)
            ->where('slug', $slug)
            ->whereNull('deleted_at');

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public function typeExists(int $applicationId, string $type, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('application_id', $applicationId)
            ->where('type', $type)
            ->whereNull('deleted_at');

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createEnvironment(array $data): ApplicationEnvironment
    {
        /** @var ApplicationEnvironment $environment */
        $environment = $this->model->newQuery()->create($data);

        return $environment->fresh(['application', 'creator', 'updater']) ?? $environment;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateEnvironment(ApplicationEnvironment $environment, array $data): ApplicationEnvironment
    {
        $environment->fill($data);
        $environment->save();

        return $environment->refresh()->load(['application', 'creator', 'updater']);
    }

    public function clearCurrentForApplication(int $applicationId, ?int $exceptId = null): void
    {
        $query = $this->model->newQuery()
            ->where('application_id', $applicationId)
            ->where('is_current', true);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['is_current' => false]);
    }
}

<?php

namespace App\Domains\Applications\Repositories;

use App\Domains\Applications\Models\ApplicationConfiguration;
use App\Domains\Applications\Models\ApplicationConfigurationHistory;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ApplicationConfigurationRepository extends BaseRepository
{
    public function __construct(ApplicationConfiguration $model)
    {
        parent::__construct($model);
    }

    public function findForApplication(int $applicationId, string $identifier, bool $withTrashed = false): ApplicationConfiguration
    {
        $query = $this->model->newQuery()->where('application_id', $applicationId);

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var ApplicationConfiguration|null $configuration */
        $configuration = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        if (! $configuration) {
            abort(404, 'Application configuration not found.');
        }

        return $configuration;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForApplication(int $applicationId, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($applicationId, $filters)
            ->with([
                'environment:id,uuid,name,slug,type',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, ApplicationConfiguration>
     */
    public function managerCatalog(int $applicationId, ?int $environmentId = null): Collection
    {
        $query = $this->model->newQuery()
            ->where('application_id', $applicationId)
            ->with(['environment:id,uuid,name,slug,type']);

        if ($environmentId === null) {
            $query->whereNull('environment_id');
        } else {
            $query->where('environment_id', $environmentId);
        }

        return $query->orderBy('type')->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(int $applicationId, array $filters = []): Builder
    {
        $query = $this->model->newQuery()->where('application_id', $applicationId);

        if (array_key_exists('environment_id', $filters)) {
            if ($filters['environment_id'] === null || $filters['environment_id'] === '') {
                $query->whereNull('environment_id');
            } else {
                $query->where('environment_id', (int) $filters['environment_id']);
            }
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'type');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowed = ['name', 'type', 'status', 'version', 'is_active', 'created_at', 'updated_at'];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'type';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    public function typeExistsForScope(int $applicationId, ?int $environmentId, string $type, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('application_id', $applicationId)
            ->where('type', $type)
            ->whereNull('deleted_at');

        if ($environmentId === null) {
            $query->whereNull('environment_id');
        } else {
            $query->where('environment_id', $environmentId);
        }

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createConfiguration(array $data): ApplicationConfiguration
    {
        /** @var ApplicationConfiguration $configuration */
        $configuration = $this->model->newQuery()->create($data);

        return $configuration->fresh(['environment', 'creator', 'updater']) ?? $configuration;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateConfiguration(ApplicationConfiguration $configuration, array $data): ApplicationConfiguration
    {
        $configuration->fill($data);
        $configuration->save();

        return $configuration->refresh()->load(['environment', 'creator', 'updater']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createHistory(array $data): ApplicationConfigurationHistory
    {
        /** @var ApplicationConfigurationHistory $history */
        $history = ApplicationConfigurationHistory::query()->create($data);

        return $history;
    }

    /**
     * @return Collection<int, ApplicationConfigurationHistory>
     */
    public function historyForConfiguration(int $configurationId): Collection
    {
        return ApplicationConfigurationHistory::query()
            ->where('configuration_id', $configurationId)
            ->with(['creator:id,uuid,full_name,email'])
            ->orderByDesc('version')
            ->get();
    }

    public function findHistory(int $configurationId, string $identifier): ApplicationConfigurationHistory
    {
        $query = ApplicationConfigurationHistory::query()->where('configuration_id', $configurationId);

        /** @var ApplicationConfigurationHistory|null $history */
        $history = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        if (! $history) {
            abort(404, 'Configuration history entry not found.');
        }

        return $history;
    }
}

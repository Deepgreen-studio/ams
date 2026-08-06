<?php

namespace App\Domains\Integrations\Repositories;

use App\Domains\Integrations\Models\SyncConfig;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SyncConfigRepository extends BaseRepository
{
    public function __construct(SyncConfig $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): SyncConfig
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var SyncConfig|null $config */
        $config = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        if (! $config) {
            abort(404, 'Sync configuration not found.');
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with(['company:id,uuid,company_name', 'integration:id,uuid,name,slug,status'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }
        if (! empty($filters['integration_id'])) {
            $query->where('integration_id', (int) $filters['integration_id']);
        }
        if (! empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }
        if (! empty($filters['trigger_type'])) {
            $query->where('trigger_type', $filters['trigger_type']);
        }
        if (array_key_exists('is_enabled', $filters) && $filters['is_enabled'] !== '' && $filters['is_enabled'] !== null) {
            $query->where('is_enabled', filter_var($filters['is_enabled'], FILTER_VALIDATE_BOOLEAN));
        }
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('entity_type', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at');
    }

    /**
     * @return Collection<int, SyncConfig>
     */
    public function dueScheduled(): Collection
    {
        return $this->model->newQuery()
            ->where('is_enabled', true)
            ->where('trigger_type', 'scheduled')
            ->whereNotNull('schedule_cron')
            ->with('integration')
            ->get();
    }

    public function slugExists(int $companyId, string $slug, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()->where('company_id', $companyId)->where('slug', $slug)->whereNull('deleted_at');
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /** @param  array<string, mixed>  $data */
    public function createConfig(array $data): SyncConfig
    {
        /** @var SyncConfig $config */
        $config = $this->model->newQuery()->create($data);

        return $config->fresh(['company', 'integration']) ?? $config;
    }

    /** @param  array<string, mixed>  $data */
    public function updateConfig(SyncConfig $config, array $data): SyncConfig
    {
        $config->fill($data);
        $config->save();

        return $config->refresh()->load(['company', 'integration']);
    }
}

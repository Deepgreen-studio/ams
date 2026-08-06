<?php

namespace App\Domains\Integrations\Repositories;

use App\Domains\Integrations\Models\DataMapping;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DataMappingRepository extends BaseRepository
{
    public function __construct(DataMapping $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): DataMapping
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var DataMapping|null $mapping */
        $mapping = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        if (! $mapping) {
            abort(404, 'Data mapping not found.');
        }

        return $mapping;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with(['company:id,uuid,company_name', 'integration:id,uuid,name,slug,status'])
            ->withCount('fields')
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
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('source_entity', 'like', "%{$search}%")
                    ->orWhere('target_entity', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('updated_at');
    }

    public function slugExists(int $companyId, string $slug, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('company_id', $companyId)
            ->where('slug', $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createMapping(array $payload): DataMapping
    {
        /** @var DataMapping $mapping */
        $mapping = $this->model->newQuery()->create($payload);

        return $mapping;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateMapping(DataMapping $mapping, array $payload): DataMapping
    {
        $mapping->update($payload);

        return $mapping->fresh();
    }
}

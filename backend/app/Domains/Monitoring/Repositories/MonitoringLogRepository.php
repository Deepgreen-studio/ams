<?php

namespace App\Domains\Monitoring\Repositories;

use App\Domains\Monitoring\Models\MonitoringLog;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MonitoringLogRepository extends BaseRepository
{
    public function __construct(MonitoringLog $model)
    {
        parent::__construct($model);
    }

    public function findByUuidOrFail(string $uuid): MonitoringLog
    {
        /** @var MonitoringLog $log */
        $log = $this->model->newQuery()->where('uuid', $uuid)->firstOrFail();

        return $log;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        return $this->filteredQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<MonitoringLog>
     */
    public function timeline(array $filters = [], int $limit = 50): array
    {
        return $this->filteredQuery($filters)
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery()->latest('occurred_at');

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }
        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }
        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }
        if (! empty($filters['from'])) {
            $query->where('occurred_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->where('occurred_at', '<=', $filters['to']);
        }
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}

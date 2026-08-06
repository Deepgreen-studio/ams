<?php

namespace App\Domains\Integrations\Repositories;

use App\Domains\Integrations\Models\SyncLog;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SyncLogRepository extends BaseRepository
{
    public function __construct(SyncLog $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 50), 200));
        $query = $this->model->newQuery();

        if (! empty($filters['sync_run_id'])) {
            $query->where('sync_run_id', (int) $filters['sync_run_id']);
        }
        if (! empty($filters['sync_config_id'])) {
            $query->where('sync_config_id', (int) $filters['sync_config_id']);
        }
        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }
        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('message', 'like', "%{$search}%")
                    ->orWhere('record_key', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    /** @param  array<string, mixed>  $data */
    public function createLog(array $data): SyncLog
    {
        /** @var SyncLog $log */
        $log = $this->model->newQuery()->create($data);

        return $log;
    }
}

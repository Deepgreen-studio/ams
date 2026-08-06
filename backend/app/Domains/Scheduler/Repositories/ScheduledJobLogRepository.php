<?php

namespace App\Domains\Scheduler\Repositories;

use App\Domains\Scheduler\Models\ScheduledJobLog;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ScheduledJobLogRepository extends BaseRepository
{
    public function __construct(ScheduledJobLog $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 50), 200));

        $query = $this->model->newQuery()
            ->with([
                'run:id,uuid,scheduled_job_id,status',
                'run.job:id,uuid,name,handler_key',
            ])
            ->latest('id');

        if (! blank($filters['scheduled_job_run_id'] ?? null)) {
            $query->where('scheduled_job_run_id', (int) $filters['scheduled_job_run_id']);
        }
        if (! blank($filters['level'] ?? null)) {
            $query->where('level', $filters['level']);
        }
        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where('message', 'like', "%{$search}%");
        }

        return $query->paginate($perPage)->withQueryString();
    }
}

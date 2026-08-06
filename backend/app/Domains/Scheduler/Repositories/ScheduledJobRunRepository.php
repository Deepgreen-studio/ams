<?php

namespace App\Domains\Scheduler\Repositories;

use App\Domains\Scheduler\Enums\ScheduledJobRunStatus;
use App\Domains\Scheduler\Models\ScheduledJobRun;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ScheduledJobRunRepository extends BaseRepository
{
    public function __construct(ScheduledJobRun $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier): ?ScheduledJobRun
    {
        /** @var ScheduledJobRun|null $run */
        $run = $this->model->newQuery()
            ->where(function (Builder $builder) use ($identifier): void {
                $builder->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $builder->orWhere('id', (int) $identifier);
                }
            })
            ->first();

        return $run;
    }

    public function findByIdentifierOrFail(string $identifier): ScheduledJobRun
    {
        $run = $this->findByIdentifier($identifier);
        if (! $run) {
            abort(404, 'Scheduled job run not found.');
        }

        return $run;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        $query = $this->model->newQuery()
            ->with([
                'job:id,uuid,name,handler_key,job_type',
                'triggerer:id,uuid,full_name,email',
                'logs',
            ])
            ->latest('id');

        if (! blank($filters['scheduled_job_id'] ?? null)) {
            $query->where('scheduled_job_id', (int) $filters['scheduled_job_id']);
        }
        if (! blank($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('error_message', 'like', "%{$search}%")
                    ->orWhere('trigger', 'like', "%{$search}%")
                    ->orWhereHas('job', fn (Builder $job) => $job->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total' => $this->model->newQuery()->count(),
            'pending' => $this->model->newQuery()->where('status', ScheduledJobRunStatus::Pending->value)->count(),
            'queued' => $this->model->newQuery()->where('status', ScheduledJobRunStatus::Queued->value)->count(),
            'running' => $this->model->newQuery()->where('status', ScheduledJobRunStatus::Running->value)->count(),
            'success' => $this->model->newQuery()->where('status', ScheduledJobRunStatus::Success->value)->count(),
            'failed' => $this->model->newQuery()->where('status', ScheduledJobRunStatus::Failed->value)->count(),
        ];
    }
}

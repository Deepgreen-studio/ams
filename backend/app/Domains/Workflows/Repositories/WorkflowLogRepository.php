<?php

namespace App\Domains\Workflows\Repositories;

use App\Domains\Workflows\Models\WorkflowLog;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class WorkflowLogRepository extends BaseRepository
{
    public function __construct(WorkflowLog $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        $query = $this->model->newQuery()
            ->with([
                'instance:id,uuid,subject_label,status,workflow_id',
                'instance.workflow:id,uuid,name',
                'step:id,uuid,name,step_key,step_type',
                'actor:id,uuid,full_name,email',
            ])
            ->latest('id');

        if (! blank($filters['workflow_instance_id'] ?? null)) {
            $query->where('workflow_instance_id', (int) $filters['workflow_instance_id']);
        }
        if (! blank($filters['action'] ?? null)) {
            $query->where('action', $filters['action']);
        }
        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('comment', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }
}

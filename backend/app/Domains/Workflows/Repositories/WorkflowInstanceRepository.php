<?php

namespace App\Domains\Workflows\Repositories;

use App\Domains\Workflows\Enums\WorkflowInstanceStatus;
use App\Domains\Workflows\Models\WorkflowInstance;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class WorkflowInstanceRepository extends BaseRepository
{
    public function __construct(WorkflowInstance $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier): ?WorkflowInstance
    {
        /** @var WorkflowInstance|null $instance */
        $instance = $this->model->newQuery()
            ->where(function (Builder $builder) use ($identifier): void {
                $builder->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $builder->orWhere('id', (int) $identifier);
                }
            })
            ->first();

        return $instance;
    }

    public function findByIdentifierOrFail(string $identifier): WorkflowInstance
    {
        $instance = $this->findByIdentifier($identifier);
        if (! $instance) {
            abort(404, 'Workflow instance not found.');
        }

        return $instance;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        return $this->filteredQuery($filters)
            ->with([
                'workflow:id,uuid,name,type,status',
                'currentStep',
                'starter:id,uuid,full_name,email',
                'company:id,uuid,company_name',
            ])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Approval queue for a user — instances where user uuid or role is pending.
     *
     * @param  list<string>  $roleNames
     */
    public function paginateApprovalQueue(string $userUuid, array $roleNames, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        $query = $this->model->newQuery()
            ->with([
                'workflow:id,uuid,name,type',
                'currentStep',
                'starter:id,uuid,full_name,email',
            ])
            ->whereIn('status', [
                WorkflowInstanceStatus::Pending->value,
                WorkflowInstanceStatus::InProgress->value,
            ])
            ->where(function (Builder $builder) use ($userUuid, $roleNames): void {
                // Portable JSON match (MySQL + SQLite): search serialized pending_approvers payload.
                $builder->where('pending_approvers', 'like', '%"value":"'.$this->escapeLike($userUuid).'"%');
                foreach ($roleNames as $role) {
                    $builder->orWhere('pending_approvers', 'like', '%"value":"'.$this->escapeLike((string) $role).'"%');
                }
            })
            ->latest('id');

        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('subject_label', 'like', "%{$search}%")
                    ->orWhere('subject_id', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return Collection<int, WorkflowInstance>
     */
    public function dueForTimeout(int $limit = 50): Collection
    {
        return $this->model->newQuery()
            ->with(['workflow.steps', 'currentStep'])
            ->whereIn('status', [
                WorkflowInstanceStatus::Pending->value,
                WorkflowInstanceStatus::InProgress->value,
            ])
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now())
            ->orderBy('due_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total' => $this->model->newQuery()->count(),
            'pending' => $this->model->newQuery()->where('status', WorkflowInstanceStatus::Pending->value)->count(),
            'in_progress' => $this->model->newQuery()->where('status', WorkflowInstanceStatus::InProgress->value)->count(),
            'approved' => $this->model->newQuery()->where('status', WorkflowInstanceStatus::Approved->value)->count(),
            'rejected' => $this->model->newQuery()->where('status', WorkflowInstanceStatus::Rejected->value)->count(),
            'timed_out' => $this->model->newQuery()->where('status', WorkflowInstanceStatus::TimedOut->value)->count(),
            'completed' => $this->model->newQuery()->where('status', WorkflowInstanceStatus::Completed->value)->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery()->latest('id');

        if (! blank($filters['workflow_id'] ?? null)) {
            $query->where('workflow_id', (int) $filters['workflow_id']);
        }
        if (! blank($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
        if (! blank($filters['company_id'] ?? null)) {
            $query->where('company_id', (int) $filters['company_id']);
        }
        if (! blank($filters['subject_type'] ?? null)) {
            $query->where('subject_type', $filters['subject_type']);
        }
        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('subject_label', 'like', "%{$search}%")
                    ->orWhere('subject_id', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}

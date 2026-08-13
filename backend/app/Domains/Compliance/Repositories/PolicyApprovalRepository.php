<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Compliance\Enums\PolicyApprovalStatus;
use App\Domains\Compliance\Models\PolicyApproval;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PolicyApprovalRepository extends BaseRepository
{
    public function __construct(PolicyApproval $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifierOrFail(string $identifier): PolicyApproval
    {
        /** @var PolicyApproval|null $approval */
        $approval = $this->model->newQuery()
            ->where('uuid', $identifier)
            ->when(ctype_digit($identifier), fn ($q) => $q->orWhere('id', (int) $identifier))
            ->first();

        if (! $approval) {
            abort(404, 'Policy approval not found.');
        }

        return $approval;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createApproval(array $data): PolicyApproval
    {
        /** @var PolicyApproval $approval */
        $approval = $this->model->newQuery()->create($data);

        return $approval->fresh([
            'policy',
            'version',
            'requester',
            'reviewer',
        ]) ?? $approval;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateApproval(PolicyApproval $approval, array $data): PolicyApproval
    {
        $approval->fill($data);
        $approval->save();

        return $approval->refresh()->load([
            'policy',
            'version',
            'requester',
            'reviewer',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        $query = $this->model->newQuery()
            ->with([
                'policy:id,uuid,policy_number,title,status,policy_type,company_id,current_version',
                'policy.company:id,uuid,company_name',
                'version:id,uuid,version,status,title',
                'requester:id,uuid,full_name,email',
                'reviewer:id,uuid,full_name,email',
            ]);

        if (! empty($filters['company_id'])) {
            $query->whereHas('policy', function (Builder $builder) use ($filters): void {
                $builder->where('company_id', (int) $filters['company_id']);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', PolicyApprovalStatus::Pending->value);
        }

        if (! empty($filters['policy_id'])) {
            $query->where('policy_id', (int) $filters['policy_id']);
        }

        return $query->orderByDesc('requested_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function statistics(?int $companyId = null): array
    {
        $base = $this->model->newQuery();

        if ($companyId !== null) {
            $base->whereHas('policy', function (Builder $builder) use ($companyId): void {
                $builder->where('company_id', $companyId);
            });
        }

        return [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', PolicyApprovalStatus::Pending->value)->count(),
            'approved' => (clone $base)->where('status', PolicyApprovalStatus::Approved->value)->count(),
            'rejected' => (clone $base)->where('status', PolicyApprovalStatus::Rejected->value)->count(),
            'cancelled' => (clone $base)->where('status', PolicyApprovalStatus::Cancelled->value)->count(),
        ];
    }

    public function cancelPendingForPolicy(int $policyId): void
    {
        $this->model->newQuery()
            ->where('policy_id', $policyId)
            ->where('status', PolicyApprovalStatus::Pending->value)
            ->update([
                'status' => PolicyApprovalStatus::Cancelled->value,
                'decided_at' => now(),
            ]);
    }
}

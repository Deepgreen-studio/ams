<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Compliance\Enums\RiskActionStatus;
use App\Domains\Compliance\Models\RiskAction;
use App\Domains\Compliance\Models\RiskRegister;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class RiskActionRepository extends BaseRepository
{
    public function __construct(RiskAction $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier): ?RiskAction
    {
        /** @var RiskAction|null $action */
        $action = $this->model->newQuery()
            ->where('uuid', $identifier)
            ->when(ctype_digit($identifier), fn ($q) => $q->orWhere('id', (int) $identifier))
            ->first();

        return $action;
    }

    public function findByIdentifierOrFail(string $identifier): RiskAction
    {
        $action = $this->findByIdentifier($identifier);

        if (! $action) {
            abort(404, 'Risk action not found.');
        }

        return $action;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForRisk(RiskRegister $risk, array $data): RiskAction
    {
        $data['risk_register_id'] = $risk->id;

        /** @var RiskAction $action */
        $action = $this->model->newQuery()->create($data);

        return $action->fresh(['performer', 'riskRegister']) ?? $action;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAction(RiskAction $action, array $data): RiskAction
    {
        $action->fill($data);
        $action->save();

        return $action->refresh()->load(['performer', 'riskRegister']);
    }

    /**
     * @return Collection<int, RiskAction>
     */
    public function forRisk(int $riskId): Collection
    {
        return $this->model->newQuery()
            ->with(['performer:id,uuid,full_name,email'])
            ->where('risk_register_id', $riskId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        $query = $this->model->newQuery()
            ->with([
                'performer:id,uuid,full_name,email',
                'riskRegister:id,uuid,risk_number,title,company_id,status,risk_level',
                'riskRegister.company:id,uuid,company_name',
            ]);

        if (! empty($filters['company_id'])) {
            $query->whereHas('riskRegister', function (Builder $builder) use ($filters): void {
                $builder->where('company_id', (int) $filters['company_id']);
            });
        }

        if (! empty($filters['risk_register_id'])) {
            $query->where('risk_register_id', (int) $filters['risk_register_id']);
        }

        if (! empty($filters['action_type'])) {
            $query->where('action_type', $filters['action_type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (($filters['open'] ?? null) === true || ($filters['open'] ?? null) === '1') {
            $query->whereIn('status', [
                RiskActionStatus::Planned->value,
                RiskActionStatus::InProgress->value,
            ]);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function recordTimeline(
        RiskRegister $risk,
        string $actionType,
        string $title,
        ?string $description,
        ?string $fromStatus,
        ?string $toStatus,
        ?int $performedBy,
        ?array $metadata = null
    ): RiskAction {
        return $this->createForRisk($risk, [
            'action_type' => $actionType,
            'title' => $title,
            'description' => $description,
            'status' => RiskActionStatus::Completed->value,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'performed_by' => $performedBy,
            'completed_at' => now(),
            'metadata' => $metadata,
        ]);
    }
}

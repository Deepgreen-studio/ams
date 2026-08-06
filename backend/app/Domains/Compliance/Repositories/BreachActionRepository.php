<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Compliance\Models\BreachAction;
use App\Domains\Compliance\Models\DataBreach;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class BreachActionRepository extends BaseRepository
{
    public function __construct(BreachAction $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier): ?BreachAction
    {
        /** @var BreachAction|null $action */
        $action = $this->model->newQuery()
            ->where('uuid', $identifier)
            ->when(ctype_digit($identifier), fn ($q) => $q->orWhere('id', (int) $identifier))
            ->first();

        return $action;
    }

    public function findByIdentifierOrFail(string $identifier): BreachAction
    {
        $action = $this->findByIdentifier($identifier);

        if (! $action) {
            abort(404, 'Breach action not found.');
        }

        return $action;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForBreach(DataBreach $breach, array $data): BreachAction
    {
        $data['data_breach_id'] = $breach->id;

        /** @var BreachAction $action */
        $action = $this->model->newQuery()->create($data);

        return $action->fresh(['performer']) ?? $action;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAction(BreachAction $action, array $data): BreachAction
    {
        $action->fill($data);
        $action->save();

        return $action->refresh()->load('performer');
    }

    /**
     * @return Collection<int, BreachAction>
     */
    public function forBreach(int $breachId): Collection
    {
        return $this->model->newQuery()
            ->with(['performer:id,uuid,full_name,email'])
            ->where('data_breach_id', $breachId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function recordTimeline(
        DataBreach $breach,
        string $actionType,
        string $title,
        ?string $description,
        ?string $fromStatus,
        ?string $toStatus,
        ?int $performedBy,
        ?array $metadata = null
    ): BreachAction {
        return $this->createForBreach($breach, [
            'action_type' => $actionType,
            'title' => $title,
            'description' => $description,
            'status' => 'completed',
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'performed_by' => $performedBy,
            'completed_at' => now(),
            'metadata' => $metadata,
        ]);
    }
}

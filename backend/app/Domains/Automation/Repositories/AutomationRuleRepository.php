<?php

namespace App\Domains\Automation\Repositories;

use App\Domains\Automation\Enums\AutomationTriggerType;
use App\Domains\Automation\Models\AutomationRule;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
class AutomationRuleRepository extends BaseRepository
{
    public function __construct(AutomationRule $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?AutomationRule
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var AutomationRule|null $rule */
        $rule = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $rule;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): AutomationRule
    {
        $rule = $this->findByIdentifier($identifier, $withTrashed);
        if (! $rule) {
            abort(404, 'Automation rule not found.');
        }

        return $rule;
    }

    /**
     * @return list<string>
     */
    protected function defaultRelations(): array
    {
        return [
            'company:id,uuid,company_name',
            'conditions',
            'actions',
            'creator:id,uuid,full_name,email',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        return $this->filteredQuery($filters)
            ->with($this->defaultRelations())
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, AutomationRule>
     */
    public function enabledForEvent(string $eventKey): Collection
    {
        return $this->model->newQuery()
            ->with(['conditions', 'actions' => fn ($q) => $q->where('is_enabled', true)->orderBy('sort_order')])
            ->where('is_enabled', true)
            ->where('event_key', $eventKey)
            ->whereIn('trigger_type', [
                AutomationTriggerType::Event->value,
                AutomationTriggerType::Time->value,
            ])
            ->orderBy('priority')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, AutomationRule>
     */
    public function dueScheduled(int $limit = 50): Collection
    {
        return $this->model->newQuery()
            ->with(['conditions', 'actions' => fn ($q) => $q->where('is_enabled', true)->orderBy('sort_order')])
            ->where('is_enabled', true)
            ->where('trigger_type', AutomationTriggerType::Schedule->value)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->orderBy('next_run_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, AutomationRule>
     */
    public function dueDelayed(int $limit = 50): Collection
    {
        return $this->model->newQuery()
            ->with(['conditions', 'actions' => fn ($q) => $q->where('is_enabled', true)->orderBy('sort_order')])
            ->where('is_enabled', true)
            ->where('trigger_type', AutomationTriggerType::Time->value)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->orderBy('next_run_at')
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
            'enabled' => $this->model->newQuery()->where('is_enabled', true)->count(),
            'disabled' => $this->model->newQuery()->where('is_enabled', false)->count(),
            'event' => $this->model->newQuery()->where('trigger_type', AutomationTriggerType::Event->value)->count(),
            'schedule' => $this->model->newQuery()->where('trigger_type', AutomationTriggerType::Schedule->value)->count(),
            'time' => $this->model->newQuery()->where('trigger_type', AutomationTriggerType::Time->value)->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery()->orderBy('priority')->orderByDesc('id');

        if (! blank($filters['company_id'] ?? null)) {
            $query->where('company_id', (int) $filters['company_id']);
        }
        if (! blank($filters['trigger_type'] ?? null)) {
            $query->where('trigger_type', $filters['trigger_type']);
        }
        if (! blank($filters['event_key'] ?? null)) {
            $query->where('event_key', $filters['event_key']);
        }
        if (array_key_exists('is_enabled', $filters) && $filters['is_enabled'] !== null && $filters['is_enabled'] !== '') {
            $query->where('is_enabled', filter_var($filters['is_enabled'], FILTER_VALIDATE_BOOLEAN));
        }
        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('event_key', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}

<?php

namespace App\Domains\Customers\Repositories;

use App\Domains\Customers\Models\CustomerTask;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CustomerTaskRepository extends BaseRepository
{
    public function __construct(CustomerTask $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?CustomerTask
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var CustomerTask|null $task */
        $task = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $task;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): CustomerTask
    {
        $task = $this->findByIdentifier($identifier, $withTrashed);
        if (! $task) {
            abort(404, 'Customer task not found.');
        }

        return $task;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with([
                'customer:id,uuid,first_name,last_name,company_name,email',
                'assignee:id,uuid,full_name,email',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', (int) $filters['assigned_to']);
        }
        if (! empty($filters['search'])) {
            $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $filters['search']).'%';
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('title', 'like', $search)->orWhere('description', 'like', $search);
            });
        }

        $sortBy = in_array($filters['sort_by'] ?? 'due_at', ['due_at', 'remind_at', 'created_at', 'priority', 'status', 'title'], true)
            ? ($filters['sort_by'] ?? 'due_at')
            : 'due_at';
        $sortDir = ($filters['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @return Collection<int, CustomerTask>
     */
    public function reminders(?int $customerId = null, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfMonth()->addMonth();

        $query = $this->model->newQuery()
            ->with(['assignee:id,uuid,full_name,email', 'customer:id,uuid,first_name,last_name,company_name'])
            ->where(function (Builder $builder) use ($from, $to): void {
                $builder->whereBetween('remind_at', [$from, $to])
                    ->orWhereBetween('due_at', [$from, $to]);
            })
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderByRaw('COALESCE(remind_at, due_at) asc');

        if ($customerId !== null) {
            $query->where('customer_id', $customerId);
        }

        return $query->get();
    }

    /**
     * @return array<string, int>
     */
    public function statistics(?int $customerId = null): array
    {
        $base = $this->model->newQuery();
        if ($customerId !== null) {
            $base->where('customer_id', $customerId);
        }

        return [
            'total' => (clone $base)->count(),
            'open' => (clone $base)->where('status', 'open')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'overdue' => (clone $base)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->count(),
            'upcoming_reminders' => (clone $base)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereNotNull('remind_at')
                ->whereBetween('remind_at', [now(), now()->addDays(14)])
                ->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTask(array $data): CustomerTask
    {
        /** @var CustomerTask $task */
        $task = $this->model->newQuery()->create($data);

        return $task;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTask(CustomerTask $task, array $data): CustomerTask
    {
        $task->fill($data);
        $task->save();

        return $task->refresh();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function timeline(CustomerTask $task, int $limit = 50): Collection
    {
        $activityModel = config('activitylog.activity_model');

        return $activityModel::query()
            ->forSubject($task)
            ->with(['causer:id,uuid,full_name,email'])
            ->latest()
            ->limit(max(1, min($limit, 100)))
            ->get()
            ->map(static function ($activity): array {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'event' => $activity->event,
                    'log_name' => $activity->log_name,
                    'created_at' => $activity->created_at,
                    'properties' => $activity->properties,
                    'causer' => $activity->causer ? [
                        'id' => $activity->causer->id,
                        'uuid' => $activity->causer->uuid,
                        'full_name' => $activity->causer->full_name,
                        'email' => $activity->causer->email,
                    ] : null,
                ];
            });
    }
}

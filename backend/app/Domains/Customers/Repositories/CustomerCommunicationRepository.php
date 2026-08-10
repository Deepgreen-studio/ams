<?php

namespace App\Domains\Customers\Repositories;

use App\Domains\Customers\Models\CustomerCommunication;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CustomerCommunicationRepository extends BaseRepository
{
    public function __construct(CustomerCommunication $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?CustomerCommunication
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var CustomerCommunication|null $communication */
        $communication = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $communication;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): CustomerCommunication
    {
        $communication = $this->findByIdentifier($identifier, $withTrashed);
        if (! $communication) {
            abort(404, 'Customer communication not found.');
        }

        return $communication;
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
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $filters['search']).'%';
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('subject', 'like', $search)->orWhere('body', 'like', $search);
            });
        }

        return $query->orderBy(
            in_array($filters['sort_by'] ?? 'occurred_at', ['occurred_at', 'created_at', 'type', 'status'], true)
                ? ($filters['sort_by'] ?? 'occurred_at')
                : 'occurred_at',
            ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc'
        );
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
            'email' => (clone $base)->where('type', 'email')->count(),
            'call' => (clone $base)->where('type', 'call')->count(),
            'meeting' => (clone $base)->where('type', 'meeting')->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCommunication(array $data): CustomerCommunication
    {
        /** @var CustomerCommunication $communication */
        $communication = $this->model->newQuery()->create($data);

        return $communication;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCommunication(CustomerCommunication $communication, array $data): CustomerCommunication
    {
        $communication->fill($data);
        $communication->save();

        return $communication->refresh();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function timeline(CustomerCommunication $communication, int $limit = 50): Collection
    {
        $activityModel = config('activitylog.activity_model');

        return $activityModel::query()
            ->forSubject($communication)
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

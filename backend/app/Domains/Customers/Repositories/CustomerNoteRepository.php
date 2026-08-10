<?php

namespace App\Domains\Customers\Repositories;

use App\Domains\Customers\Models\CustomerNote;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CustomerNoteRepository extends BaseRepository
{
    public function __construct(CustomerNote $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?CustomerNote
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var CustomerNote|null $note */
        $note = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $note;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): CustomerNote
    {
        $note = $this->findByIdentifier($identifier, $withTrashed);
        if (! $note) {
            abort(404, 'Customer note not found.');
        }

        return $note;
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
        if (! empty($filters['note_type'])) {
            $query->where('note_type', $filters['note_type']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (($filters['pinned'] ?? null) === true || ($filters['pinned'] ?? null) === '1') {
            $query->where('is_pinned', true);
        }
        if (! empty($filters['search'])) {
            $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $filters['search']).'%';
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('title', 'like', $search)->orWhere('body', 'like', $search);
            });
        }

        return $query
            ->orderByDesc('is_pinned')
            ->orderBy(($filters['sort_by'] ?? 'occurred_at') === 'created_at' ? 'created_at' : 'occurred_at', ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc');
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
            'general' => (clone $base)->where('note_type', 'general')->count(),
            'internal' => (clone $base)->where('note_type', 'internal')->count(),
            'meeting' => (clone $base)->where('note_type', 'meeting')->count(),
            'pinned' => (clone $base)->where('is_pinned', true)->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createNote(array $data): CustomerNote
    {
        /** @var CustomerNote $note */
        $note = $this->model->newQuery()->create($data);

        return $note;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateNote(CustomerNote $note, array $data): CustomerNote
    {
        $note->fill($data);
        $note->save();

        return $note->refresh();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function timeline(CustomerNote $note, int $limit = 50): Collection
    {
        $activityModel = config('activitylog.activity_model');

        return $activityModel::query()
            ->forSubject($note)
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

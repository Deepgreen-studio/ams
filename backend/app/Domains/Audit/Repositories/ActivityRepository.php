<?php

namespace App\Domains\Audit\Repositories;

use App\Domains\Audit\Models\ActivityLog;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ActivityRepository extends BaseRepository
{
    public function __construct(ActivityLog $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier): ?ActivityLog
    {
        $query = $this->model->newQuery()->with(['causer', 'subject']);

        if (ctype_digit($identifier)) {
            /** @var ActivityLog|null $activity */
            $activity = $query->where('id', (int) $identifier)->first();

            return $activity;
        }

        return null;
    }

    public function findByIdentifierOrFail(string $identifier): ActivityLog
    {
        $activity = $this->findByIdentifier($identifier);
        if (! $activity) {
            abort(404, 'Activity log not found.');
        }

        return $activity;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        return $this->filteredQuery($filters)
            ->with(['causer:id,uuid,full_name,email', 'subject'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ActivityLog>
     */
    public function export(array $filters = [], int $limit = 5000): Collection
    {
        return $this->filteredQuery($filters)
            ->with(['causer:id,uuid,full_name,email'])
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('description', 'like', "%{$search}%")
                    ->orWhere('log_name', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['module']) || ! empty($filters['log_name'])) {
            $query->where('log_name', $filters['module'] ?? $filters['log_name']);
        }

        if (! empty($filters['action']) || ! empty($filters['event'])) {
            $query->where('event', $filters['action'] ?? $filters['event']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('causer_type', 'App\\Models\\User')
                ->where('causer_id', $filters['user_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $sortBy = in_array(($filters['sort_by'] ?? ''), ['created_at', 'log_name', 'event', 'id'], true)
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir);
    }
}

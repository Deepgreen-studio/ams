<?php

namespace App\Domains\Audit\Repositories;

use App\Domains\Audit\Models\SystemEvent;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EventRepository extends BaseRepository
{
    public function __construct(SystemEvent $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifierOrFail(string $identifier): SystemEvent
    {
        /** @var SystemEvent|null $event */
        $event = $this->model->newQuery()
            ->where(function (Builder $builder) use ($identifier): void {
                $builder->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $builder->orWhere('id', (int) $identifier);
                }
            })->first();

        if (! $event) {
            abort(404, 'System event not found.');
        }

        return $event;
    }

    /**
     * @return Collection<int, SystemEvent>
     */
    public function forCompany(string $companyUuid, int $limit = 30): Collection
    {
        return $this->model->newQuery()
            ->where('module', 'companies')
            ->where('payload->company_uuid', $companyUuid)
            ->latest('id')
            ->limit(max(1, min($limit, 100)))
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));
        $query = $this->model->newQuery();

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('event', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (! empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();
    }
}

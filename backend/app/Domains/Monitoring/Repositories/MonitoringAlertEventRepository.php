<?php

namespace App\Domains\Monitoring\Repositories;

use App\Domains\Monitoring\Models\MonitoringAlertEvent;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MonitoringAlertEventRepository extends BaseRepository
{
    public function __construct(MonitoringAlertEvent $model)
    {
        parent::__construct($model);
    }

    public function findByUuidOrFail(string $uuid): MonitoringAlertEvent
    {
        /** @var MonitoringAlertEvent|null $event */
        $event = $this->model->newQuery()->where('uuid', $uuid)->first();
        if (! $event) {
            abort(404, 'Alert event not found.');
        }

        return $event;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));
        $query = $this->model->newQuery()->with(['alert:id,uuid,name,metric', 'acknowledger:id,uuid,full_name,email']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('message', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();
    }
}

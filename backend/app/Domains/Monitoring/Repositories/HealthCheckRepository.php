<?php

namespace App\Domains\Monitoring\Repositories;

use App\Domains\Monitoring\Models\HealthCheck;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class HealthCheckRepository extends BaseRepository
{
    public function __construct(HealthCheck $model)
    {
        parent::__construct($model);
    }

    public function findByUuidOrFail(string $uuid): HealthCheck
    {
        /** @var HealthCheck $check */
        $check = $this->model->newQuery()->where('uuid', $uuid)->firstOrFail();

        return $check;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        return $this->filteredQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, HealthCheck>
     */
    public function latestByType(?int $companyId = null): Collection
    {
        $query = $this->model->newQuery()
            ->whereIn('id', function ($sub) use ($companyId): void {
                $sub->selectRaw('MAX(id)')
                    ->from('health_checks')
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->groupBy('check_type');
            })
            ->orderBy('check_type');

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery()->latest('checked_at');

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }
        if (! empty($filters['check_type'])) {
            $query->where('check_type', $filters['check_type']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['monitoring_snapshot_id'])) {
            $query->where('monitoring_snapshot_id', (int) $filters['monitoring_snapshot_id']);
        }

        return $query;
    }
}

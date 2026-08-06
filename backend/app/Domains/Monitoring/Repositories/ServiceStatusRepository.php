<?php

namespace App\Domains\Monitoring\Repositories;

use App\Domains\Monitoring\Models\ServiceStatus;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ServiceStatusRepository extends BaseRepository
{
    public function __construct(ServiceStatus $model)
    {
        parent::__construct($model);
    }

    public function findByUuidOrFail(string $uuid): ServiceStatus
    {
        /** @var ServiceStatus $status */
        $status = $this->model->newQuery()->where('uuid', $uuid)->firstOrFail();

        return $status;
    }

    public function upsertByKey(?int $companyId, string $serviceKey, array $attributes): ServiceStatus
    {
        $existing = $this->model->newQuery()
            ->when($companyId === null, fn ($q) => $q->whereNull('company_id'), fn ($q) => $q->where('company_id', $companyId))
            ->where('service_key', $serviceKey)
            ->first();

        if ($existing) {
            $existing->update($attributes);

            return $existing->fresh();
        }

        /** @var ServiceStatus $created */
        $created = $this->create(array_merge($attributes, [
            'company_id' => $companyId,
            'service_key' => $serviceKey,
        ]));

        return $created;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ServiceStatus>
     */
    public function listFiltered(array $filters = []): Collection
    {
        return $this->filteredQuery($filters)->orderBy('service_type')->orderBy('name')->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (array_key_exists('company_id', $filters) && $filters['company_id'] !== null && $filters['company_id'] !== '') {
            $query->where('company_id', (int) $filters['company_id']);
        }
        if (! empty($filters['service_type'])) {
            $query->where('service_type', $filters['service_type']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('service_key', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}

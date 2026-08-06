<?php

namespace App\Domains\Monitoring\Repositories;

use App\Domains\Monitoring\Models\MonitoringAlert;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MonitoringAlertRepository extends BaseRepository
{
    public function __construct(MonitoringAlert $model)
    {
        parent::__construct($model);
    }

    public function findByUuidOrFail(string $uuid): MonitoringAlert
    {
        /** @var MonitoringAlert|null $alert */
        $alert = $this->model->newQuery()->where('uuid', $uuid)->first();
        if (! $alert) {
            abort(404, 'Monitoring alert not found.');
        }

        return $alert;
    }

    /**
     * @return Collection<int, MonitoringAlert>
     */
    public function enabledForCompany(?int $companyId = null): Collection
    {
        return $this->model->newQuery()
            ->where('is_enabled', true)
            ->when($companyId, fn ($q) => $q->where(function (Builder $builder) use ($companyId): void {
                $builder->whereNull('company_id')->orWhere('company_id', $companyId);
            }), fn ($q) => $q->whereNull('company_id'))
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));
        $query = $this->model->newQuery()->with('company:id,uuid,company_name');

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }
        if (! empty($filters['metric'])) {
            $query->where('metric', $filters['metric']);
        }
        if (array_key_exists('is_enabled', $filters) && $filters['is_enabled'] !== '' && $filters['is_enabled'] !== null) {
            $query->where('is_enabled', filter_var($filters['is_enabled'], FILTER_VALIDATE_BOOLEAN));
        }
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('updated_at')->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createAlert(array $payload): MonitoringAlert
    {
        /** @var MonitoringAlert $alert */
        $alert = $this->model->newQuery()->create($payload);

        return $alert;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateAlert(MonitoringAlert $alert, array $payload): MonitoringAlert
    {
        $alert->update($payload);

        return $alert->fresh() ?? $alert;
    }
}

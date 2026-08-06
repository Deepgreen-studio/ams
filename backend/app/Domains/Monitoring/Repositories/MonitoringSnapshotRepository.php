<?php

namespace App\Domains\Monitoring\Repositories;

use App\Domains\Monitoring\Models\MonitoringSnapshot;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class MonitoringSnapshotRepository extends BaseRepository
{
    public function __construct(MonitoringSnapshot $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createSnapshot(array $payload): MonitoringSnapshot
    {
        /** @var MonitoringSnapshot $snapshot */
        $snapshot = $this->model->newQuery()->create($payload);

        return $snapshot;
    }

    /**
     * @return Collection<int, MonitoringSnapshot>
     */
    public function recent(int $limit = 24, ?int $companyId = null, ?int $integrationId = null): Collection
    {
        return $this->model->newQuery()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($integrationId, fn ($q) => $q->where('integration_id', $integrationId))
            ->when(! $integrationId, fn ($q) => $q->where('scope', 'hub'))
            ->latest()
            ->limit($limit)
            ->get();
    }
}

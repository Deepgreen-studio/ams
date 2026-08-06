<?php

namespace App\Domains\Analytics\Repositories;

use App\Domains\Analytics\Models\BusinessAnalyticsSnapshot;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BusinessAnalyticsSnapshotRepository extends BaseRepository
{
    public function __construct(BusinessAnalyticsSnapshot $model)
    {
        parent::__construct($model);
    }

    public function findByUuidOrFail(string $uuid): BusinessAnalyticsSnapshot
    {
        /** @var BusinessAnalyticsSnapshot $snapshot */
        $snapshot = $this->model->newQuery()->where('uuid', $uuid)->firstOrFail();

        return $snapshot;
    }

    public function upsertForDate(?int $companyId, Carbon $date, array $attributes): BusinessAnalyticsSnapshot
    {
        $existing = $this->model->newQuery()
            ->when($companyId === null, fn ($q) => $q->whereNull('company_id'), fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('snapshot_date', $date->toDateString())
            ->first();

        $payload = array_merge($attributes, [
            'company_id' => $companyId,
            'snapshot_date' => $date->toDateString(),
            'computed_at' => now(),
        ]);

        if ($existing) {
            $existing->update($payload);

            return $existing->fresh();
        }

        /** @var BusinessAnalyticsSnapshot $created */
        $created = $this->create($payload);

        return $created;
    }

    /**
     * @return Collection<int, BusinessAnalyticsSnapshot>
     */
    public function history(?int $companyId, Carbon $from, Carbon $to): Collection
    {
        return $this->model->newQuery()
            ->when($companyId === null, fn ($q) => $q->whereNull('company_id'), fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('snapshot_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('snapshot_date')
            ->get();
    }
}

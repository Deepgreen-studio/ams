<?php

namespace App\Domains\Analytics\Repositories;

use App\Domains\Analytics\Models\ExecutiveAnalyticsSnapshot;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ExecutiveAnalyticsSnapshotRepository extends BaseRepository
{
    public function __construct(ExecutiveAnalyticsSnapshot $model)
    {
        parent::__construct($model);
    }

    public function upsertForDate(?int $companyId, Carbon $date, array $attributes): ExecutiveAnalyticsSnapshot
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

        /** @var ExecutiveAnalyticsSnapshot $created */
        $created = $this->create($payload);

        return $created;
    }

    /**
     * @return Collection<int, ExecutiveAnalyticsSnapshot>
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

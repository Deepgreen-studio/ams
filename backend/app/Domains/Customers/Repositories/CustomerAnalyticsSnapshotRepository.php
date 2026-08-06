<?php

namespace App\Domains\Customers\Repositories;

use App\Domains\Customers\Models\CustomerAnalyticsSnapshot;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CustomerAnalyticsSnapshotRepository extends BaseRepository
{
    public function __construct(CustomerAnalyticsSnapshot $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertForDate(int $customerId, Carbon $date, array $data): CustomerAnalyticsSnapshot
    {
        $dateString = $date->toDateString();

        /** @var CustomerAnalyticsSnapshot|null $snapshot */
        $snapshot = $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->whereDate('snapshot_date', $dateString)
            ->first();

        $payload = array_merge($data, [
            'snapshot_date' => $dateString,
            'computed_at' => now(),
        ]);

        if ($snapshot) {
            $snapshot->fill($payload);
            $snapshot->save();

            return $snapshot->refresh();
        }

        /** @var CustomerAnalyticsSnapshot $created */
        $created = $this->model->newQuery()->create(array_merge($payload, [
            'customer_id' => $customerId,
        ]));

        return $created->refresh();
    }

    public function latestForCustomer(int $customerId): ?CustomerAnalyticsSnapshot
    {
        /** @var CustomerAnalyticsSnapshot|null $snapshot */
        $snapshot = $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->orderByDesc('snapshot_date')
            ->first();

        return $snapshot;
    }

    /**
     * @return Collection<int, CustomerAnalyticsSnapshot>
     */
    public function forRange(int $customerId, Carbon $from, Carbon $to): Collection
    {
        return $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->whereDate('snapshot_date', '>=', $from->toDateString())
            ->whereDate('snapshot_date', '<=', $to->toDateString())
            ->orderBy('snapshot_date')
            ->get();
    }
}

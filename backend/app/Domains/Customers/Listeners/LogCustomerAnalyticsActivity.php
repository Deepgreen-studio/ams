<?php

namespace App\Domains\Customers\Listeners;

use App\Domains\Customers\Events\CustomerAnalyticsSnapshotComputed;

class LogCustomerAnalyticsActivity
{
    public function handleCustomerAnalyticsSnapshotComputed(CustomerAnalyticsSnapshotComputed $event): void
    {
        activity('customer_analytics')
            ->causedBy($event->actor)
            ->performedOn($event->snapshot)
            ->withProperties([
                'event' => 'customer_analytics_snapshot_computed',
                'customer_id' => $event->snapshot->customer_id,
                'snapshot_date' => optional($event->snapshot->snapshot_date)->toDateString(),
                'health_score' => $event->snapshot->health_score,
                'activity_score' => $event->snapshot->activity_score,
                'risk_level' => $event->snapshot->risk_level?->value ?? $event->snapshot->risk_level,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer analytics snapshot computed');
    }
}

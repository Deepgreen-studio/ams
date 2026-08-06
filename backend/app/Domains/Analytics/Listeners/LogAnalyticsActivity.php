<?php

namespace App\Domains\Analytics\Listeners;

use App\Domains\Analytics\Events\AnalyticsDashboardCreated;
use App\Domains\Analytics\Events\AnalyticsDashboardDeleted;
use App\Domains\Analytics\Events\AnalyticsDashboardUpdated;
use App\Domains\Analytics\Events\AnalyticsEventRecorded;
use App\Domains\Analytics\Events\AnalyticsWidgetCreated;
use App\Domains\Analytics\Events\AnalyticsWidgetDeleted;
use App\Domains\Analytics\Events\AnalyticsWidgetUpdated;

class LogAnalyticsActivity
{
    public function handleAnalyticsEventRecorded(AnalyticsEventRecorded $event): void
    {
        $logger = activity('analytics')->performedOn($event->analyticsEvent);

        if ($event->actor) {
            $logger->causedBy($event->actor);
        }

        $logger->withProperties([
            'event' => 'analytics_event_recorded',
            'category' => $event->analyticsEvent->category?->value ?? $event->analyticsEvent->category,
            'event_name' => $event->analyticsEvent->event_name,
        ])->log('Analytics event recorded');
    }

    public function handleDashboardCreated(AnalyticsDashboardCreated $event): void
    {
        activity('analytics')
            ->causedBy($event->actor)
            ->performedOn($event->dashboard)
            ->withProperties([
                'event' => 'analytics_dashboard_created',
                'kind' => $event->dashboard->kind?->value ?? $event->dashboard->kind,
                'category' => $event->dashboard->category?->value ?? $event->dashboard->category,
            ])
            ->log('Analytics dashboard created');
    }

    public function handleDashboardUpdated(AnalyticsDashboardUpdated $event): void
    {
        activity('analytics')
            ->causedBy($event->actor)
            ->performedOn($event->dashboard)
            ->withProperties([
                'event' => 'analytics_dashboard_updated',
                'status' => $event->dashboard->status?->value ?? $event->dashboard->status,
            ])
            ->log('Analytics dashboard updated');
    }

    public function handleDashboardDeleted(AnalyticsDashboardDeleted $event): void
    {
        activity('analytics')
            ->causedBy($event->actor)
            ->performedOn($event->dashboard)
            ->withProperties([
                'event' => $event->forceDeleted ? 'analytics_dashboard_force_deleted' : 'analytics_dashboard_deleted',
                'force_deleted' => $event->forceDeleted,
            ])
            ->log($event->forceDeleted ? 'Analytics dashboard permanently deleted' : 'Analytics dashboard deleted');
    }

    public function handleWidgetCreated(AnalyticsWidgetCreated $event): void
    {
        activity('analytics')
            ->causedBy($event->actor)
            ->performedOn($event->widget)
            ->withProperties([
                'event' => 'analytics_widget_created',
                'type' => $event->widget->type?->value ?? $event->widget->type,
                'dashboard_id' => $event->widget->analytics_dashboard_id,
            ])
            ->log('Analytics widget created');
    }

    public function handleWidgetUpdated(AnalyticsWidgetUpdated $event): void
    {
        activity('analytics')
            ->causedBy($event->actor)
            ->performedOn($event->widget)
            ->withProperties([
                'event' => 'analytics_widget_updated',
                'type' => $event->widget->type?->value ?? $event->widget->type,
            ])
            ->log('Analytics widget updated');
    }

    public function handleWidgetDeleted(AnalyticsWidgetDeleted $event): void
    {
        activity('analytics')
            ->causedBy($event->actor)
            ->performedOn($event->widget)
            ->withProperties([
                'event' => 'analytics_widget_deleted',
                'dashboard_id' => $event->widget->analytics_dashboard_id,
            ])
            ->log('Analytics widget deleted');
    }
}

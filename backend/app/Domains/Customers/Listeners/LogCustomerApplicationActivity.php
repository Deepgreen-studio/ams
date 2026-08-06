<?php

namespace App\Domains\Customers\Listeners;

use App\Domains\Customers\Events\CustomerApplicationAssigned;
use App\Domains\Customers\Events\CustomerApplicationDeleted;
use App\Domains\Customers\Events\CustomerApplicationRestored;
use App\Domains\Customers\Events\CustomerApplicationUpdated;

class LogCustomerApplicationActivity
{
    public function handleCustomerApplicationAssigned(CustomerApplicationAssigned $event): void
    {
        activity('customer_applications')
            ->causedBy($event->actor)
            ->performedOn($event->assignment)
            ->withProperties([
                'event' => 'customer_application_assigned',
                'customer_id' => $event->assignment->customer_id,
                'application_id' => $event->assignment->application_id,
                'status' => $event->assignment->status?->value ?? $event->assignment->status,
                'ownership_type' => $event->assignment->ownership_type?->value ?? $event->assignment->ownership_type,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Application assigned to customer');
    }

    public function handleCustomerApplicationUpdated(CustomerApplicationUpdated $event): void
    {
        activity('customer_applications')
            ->causedBy($event->actor)
            ->performedOn($event->assignment)
            ->withProperties([
                'event' => 'customer_application_updated',
                'status' => $event->assignment->status?->value ?? $event->assignment->status,
                'ownership_type' => $event->assignment->ownership_type?->value ?? $event->assignment->ownership_type,
                'environment_id' => $event->assignment->application_environment_id,
                'integration_id' => $event->assignment->integration_id,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer application assignment updated');
    }

    public function handleCustomerApplicationDeleted(CustomerApplicationDeleted $event): void
    {
        activity('customer_applications')
            ->causedBy($event->actor)
            ->performedOn($event->assignment)
            ->withProperties([
                'event' => 'customer_application_archived',
                'application_id' => $event->assignment->application_id,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer application assignment archived');
    }

    public function handleCustomerApplicationRestored(CustomerApplicationRestored $event): void
    {
        activity('customer_applications')
            ->causedBy($event->actor)
            ->performedOn($event->assignment)
            ->withProperties([
                'event' => 'customer_application_restored',
                'application_id' => $event->assignment->application_id,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer application assignment restored');
    }
}

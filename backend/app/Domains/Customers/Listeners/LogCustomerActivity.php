<?php

namespace App\Domains\Customers\Listeners;

use App\Domains\Customers\Events\CustomerCreated;
use App\Domains\Customers\Events\CustomerDeleted;
use App\Domains\Customers\Events\CustomerRestored;
use App\Domains\Customers\Events\CustomerUpdated;

class LogCustomerActivity
{
    public function handleCustomerCreated(CustomerCreated $event): void
    {
        activity('customers')
            ->causedBy($event->actor)
            ->performedOn($event->customer)
            ->withProperties([
                'event' => 'customer_created',
                'display_name' => $event->customer->display_name,
                'email' => $event->customer->email,
                'customer_type' => $event->customer->customer_type?->value ?? $event->customer->customer_type,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer created');
    }

    public function handleCustomerUpdated(CustomerUpdated $event): void
    {
        activity('customers')
            ->causedBy($event->actor)
            ->performedOn($event->customer)
            ->withProperties([
                'event' => 'customer_updated',
                'display_name' => $event->customer->display_name,
                'email' => $event->customer->email,
                'status' => $event->customer->status?->value ?? $event->customer->status,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer updated');
    }

    public function handleCustomerDeleted(CustomerDeleted $event): void
    {
        activity('customers')
            ->causedBy($event->actor)
            ->performedOn($event->customer)
            ->withProperties([
                'event' => 'customer_archived',
                'display_name' => $event->customer->display_name,
                'email' => $event->customer->email,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer archived');
    }

    public function handleCustomerRestored(CustomerRestored $event): void
    {
        activity('customers')
            ->causedBy($event->actor)
            ->performedOn($event->customer)
            ->withProperties([
                'event' => 'customer_restored',
                'display_name' => $event->customer->display_name,
                'email' => $event->customer->email,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer restored');
    }
}

<?php

namespace App\Domains\Customers\Listeners;

use App\Domains\Customers\Events\CustomerContactCreated;
use App\Domains\Customers\Events\CustomerContactDeleted;
use App\Domains\Customers\Events\CustomerContactRestored;
use App\Domains\Customers\Events\CustomerContactUpdated;

class LogCustomerContactActivity
{
    public function handleCustomerContactCreated(CustomerContactCreated $event): void
    {
        activity('customer_contacts')
            ->causedBy($event->actor)
            ->performedOn($event->contact)
            ->withProperties([
                'event' => 'customer_contact_created',
                'name' => $event->contact->name,
                'contact_type' => $event->contact->contact_type?->value ?? $event->contact->contact_type,
                'email' => $event->contact->email,
                'customer_id' => $event->contact->customer_id,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer contact created');
    }

    public function handleCustomerContactUpdated(CustomerContactUpdated $event): void
    {
        activity('customer_contacts')
            ->causedBy($event->actor)
            ->performedOn($event->contact)
            ->withProperties([
                'event' => 'customer_contact_updated',
                'name' => $event->contact->name,
                'contact_type' => $event->contact->contact_type?->value ?? $event->contact->contact_type,
                'status' => $event->contact->status?->value ?? $event->contact->status,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer contact updated');
    }

    public function handleCustomerContactDeleted(CustomerContactDeleted $event): void
    {
        activity('customer_contacts')
            ->causedBy($event->actor)
            ->performedOn($event->contact)
            ->withProperties([
                'event' => 'customer_contact_archived',
                'name' => $event->contact->name,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer contact archived');
    }

    public function handleCustomerContactRestored(CustomerContactRestored $event): void
    {
        activity('customer_contacts')
            ->causedBy($event->actor)
            ->performedOn($event->contact)
            ->withProperties([
                'event' => 'customer_contact_restored',
                'name' => $event->contact->name,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Customer contact restored');
    }
}

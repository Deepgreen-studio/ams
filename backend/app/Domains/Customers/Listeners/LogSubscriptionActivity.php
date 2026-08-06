<?php

namespace App\Domains\Customers\Listeners;

use App\Domains\Customers\Events\SubscriptionCancelled;
use App\Domains\Customers\Events\SubscriptionCreated;
use App\Domains\Customers\Events\SubscriptionDeleted;
use App\Domains\Customers\Events\SubscriptionRestored;
use App\Domains\Customers\Events\SubscriptionUpdated;

class LogSubscriptionActivity
{
    public function handleSubscriptionCreated(SubscriptionCreated $event): void
    {
        activity('subscriptions')
            ->causedBy($event->actor)
            ->performedOn($event->subscription)
            ->withProperties([
                'event' => 'subscription_created',
                'customer_id' => $event->subscription->customer_id,
                'plan_type' => $event->subscription->plan_type?->value ?? $event->subscription->plan_type,
                'status' => $event->subscription->status?->value ?? $event->subscription->status,
                'payment_status' => $event->subscription->payment_status?->value ?? $event->subscription->payment_status,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Subscription created');
    }

    public function handleSubscriptionUpdated(SubscriptionUpdated $event): void
    {
        activity('subscriptions')
            ->causedBy($event->actor)
            ->performedOn($event->subscription)
            ->withProperties([
                'event' => 'subscription_updated',
                'plan_type' => $event->subscription->plan_type?->value ?? $event->subscription->plan_type,
                'status' => $event->subscription->status?->value ?? $event->subscription->status,
                'payment_status' => $event->subscription->payment_status?->value ?? $event->subscription->payment_status,
                'expires_at' => $event->subscription->expires_at,
                'renews_at' => $event->subscription->renews_at,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Subscription updated');
    }

    public function handleSubscriptionCancelled(SubscriptionCancelled $event): void
    {
        activity('subscriptions')
            ->causedBy($event->actor)
            ->performedOn($event->subscription)
            ->withProperties([
                'event' => 'subscription_cancelled',
                'status' => $event->subscription->status?->value ?? $event->subscription->status,
                'cancelled_at' => $event->subscription->cancelled_at,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Subscription cancelled');
    }

    public function handleSubscriptionDeleted(SubscriptionDeleted $event): void
    {
        activity('subscriptions')
            ->causedBy($event->actor)
            ->performedOn($event->subscription)
            ->withProperties([
                'event' => 'subscription_archived',
                'customer_id' => $event->subscription->customer_id,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Subscription archived');
    }

    public function handleSubscriptionRestored(SubscriptionRestored $event): void
    {
        activity('subscriptions')
            ->causedBy($event->actor)
            ->performedOn($event->subscription)
            ->withProperties([
                'event' => 'subscription_restored',
                'customer_id' => $event->subscription->customer_id,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('Subscription restored');
    }
}

<?php

namespace App\Domains\Customers\Listeners;

use App\Domains\Customers\Events\SubscriptionCancelled;
use App\Domains\Customers\Events\SubscriptionCreated;
use App\Domains\Customers\Events\SubscriptionDeleted;
use App\Domains\Customers\Events\SubscriptionRestored;
use App\Domains\Customers\Events\SubscriptionUpdated;

/**
 * Hooks for queued renewal / billing notifications (Stripe phase).
 */
class PrepareSubscriptionNotifications
{
    public function handleSubscriptionCreated(SubscriptionCreated $event): void {}

    public function handleSubscriptionUpdated(SubscriptionUpdated $event): void {}

    public function handleSubscriptionCancelled(SubscriptionCancelled $event): void {}

    public function handleSubscriptionDeleted(SubscriptionDeleted $event): void {}

    public function handleSubscriptionRestored(SubscriptionRestored $event): void {}
}

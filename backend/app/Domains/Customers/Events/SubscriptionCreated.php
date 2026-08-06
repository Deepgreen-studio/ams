<?php

namespace App\Domains\Customers\Events;

use App\Domains\Customers\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly User $actor
    ) {}
}

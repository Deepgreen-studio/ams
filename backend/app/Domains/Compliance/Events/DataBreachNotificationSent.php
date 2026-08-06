<?php

namespace App\Domains\Compliance\Events;

use App\Domains\Compliance\Models\BreachNotification;
use App\Domains\Compliance\Models\DataBreach;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DataBreachNotificationSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly DataBreach $breach,
        public readonly BreachNotification $notification,
        public readonly User $actor
    ) {}
}

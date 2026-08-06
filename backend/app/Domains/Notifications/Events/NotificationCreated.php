<?php

namespace App\Domains\Notifications\Events;

use App\Domains\Notifications\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Notification $notification,
        public readonly User $actor,
    ) {}
}

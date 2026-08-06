<?php

namespace App\Domains\Notifications\Events;

use App\Domains\Notifications\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationChannelUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly NotificationChannel $channel,
        public readonly User $actor,
    ) {}
}

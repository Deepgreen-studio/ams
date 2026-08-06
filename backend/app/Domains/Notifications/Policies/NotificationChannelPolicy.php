<?php

namespace App\Domains\Notifications\Policies;

use App\Domains\Notifications\Enums\NotificationPermission;
use App\Domains\Notifications\Models\NotificationChannel;
use App\Models\User;

class NotificationChannelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(NotificationPermission::VIEW);
    }

    public function view(User $user, NotificationChannel $channel): bool
    {
        return $user->can(NotificationPermission::VIEW);
    }

    public function update(User $user, NotificationChannel $channel): bool
    {
        return $user->can(NotificationPermission::UPDATE);
    }
}

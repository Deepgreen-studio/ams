<?php

namespace App\Domains\Notifications\Policies;

use App\Domains\Notifications\Enums\NotificationPermission;
use App\Domains\Notifications\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(NotificationPermission::VIEW);
    }

    public function view(User $user, Notification $notification): bool
    {
        return (int) $notification->user_id === (int) $user->id
            || $user->can(NotificationPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(NotificationPermission::CREATE);
    }

    public function update(User $user, Notification $notification): bool
    {
        return (int) $notification->user_id === (int) $user->id
            || $user->can(NotificationPermission::UPDATE);
    }

    public function delete(User $user, Notification $notification): bool
    {
        return (int) $notification->user_id === (int) $user->id
            || $user->can(NotificationPermission::DELETE);
    }

    public function viewTemplates(User $user): bool
    {
        return $user->can(NotificationPermission::VIEW);
    }

    public function viewLogs(User $user): bool
    {
        return $user->can(NotificationPermission::VIEW);
    }

    public function manageChannels(User $user): bool
    {
        return $user->can(NotificationPermission::UPDATE);
    }
}

<?php

namespace App\Domains\Notifications\Policies;

use App\Domains\Notifications\Enums\NotificationPermission;
use App\Domains\Notifications\Models\NotificationTemplate;
use App\Models\User;

class NotificationTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(NotificationPermission::VIEW);
    }

    public function view(User $user, NotificationTemplate $template): bool
    {
        return $user->can(NotificationPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(NotificationPermission::CREATE);
    }

    public function update(User $user, ?NotificationTemplate $template = null): bool
    {
        return $user->can(NotificationPermission::UPDATE);
    }

    public function delete(User $user, ?NotificationTemplate $template = null): bool
    {
        return $user->can(NotificationPermission::DELETE);
    }
}

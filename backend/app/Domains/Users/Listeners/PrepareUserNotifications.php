<?php

namespace App\Domains\Users\Listeners;

use App\Domains\Users\Events\AvatarUpdated;
use App\Domains\Users\Events\UserCreated;
use App\Domains\Users\Events\UserDeleted;
use App\Domains\Users\Events\UserRestored;
use App\Domains\Users\Events\UserUpdated;

/**
 * Placeholder listener reserved for future notification workflows
 * (email, push, in-app) without changing event producers.
 */
class PrepareUserNotifications
{
    public function handleUserCreated(UserCreated $event): void
    {
        // Future: queue welcome / provisioning notifications.
    }

    public function handleUserUpdated(UserUpdated $event): void
    {
        // Future: notify on profile or account changes.
    }

    public function handleUserDeleted(UserDeleted $event): void
    {
        // Future: notify security / compliance on account removal.
    }

    public function handleUserRestored(UserRestored $event): void
    {
        // Future: notify restored account stakeholders.
    }

    public function handleAvatarUpdated(AvatarUpdated $event): void
    {
        // Future: notify media pipeline / CDN invalidation.
    }
}

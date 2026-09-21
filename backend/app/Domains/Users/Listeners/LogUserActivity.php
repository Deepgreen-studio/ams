<?php

namespace App\Domains\Users\Listeners;

use App\Domains\Users\Events\AvatarUpdated;
use App\Domains\Users\Events\UserCreated;
use App\Domains\Users\Events\UserDeleted;
use App\Domains\Users\Events\UserRestored;
use App\Domains\Users\Events\UserUpdated;
use App\Domains\Users\Support\UserLifecycleAuditor;

/**
 * Writes user lifecycle actions to the enterprise Audit Trail.
 * Attribute-level create/update/delete activity remains on Spatie LogsActivity.
 */
class LogUserActivity
{
    public function handleUserCreated(UserCreated $event): void
    {
        UserLifecycleAuditor::trail(
            'created',
            $event->actor,
            $event->user,
            null,
            UserLifecycleAuditor::snapshot($event->user),
            'User account created'
        );
    }

    public function handleUserUpdated(UserUpdated $event): void
    {
        $action = $event->context === 'profile_updated' ? 'profile_updated' : 'updated';

        UserLifecycleAuditor::trail(
            $action,
            $event->actor,
            $event->user,
            $event->before,
            $event->after,
            $action === 'profile_updated' ? 'User profile updated' : 'User account updated'
        );

        $oldStatus = $event->before['status'] ?? null;
        $newStatus = $event->after['status'] ?? null;

        if ($oldStatus !== null && $newStatus !== null && $oldStatus !== $newStatus) {
            UserLifecycleAuditor::trail(
                'status_changed',
                $event->actor,
                $event->user,
                ['status' => $oldStatus],
                ['status' => $newStatus],
                sprintf('User status changed from %s to %s', $oldStatus, $newStatus)
            );
        }
    }

    public function handleUserDeleted(UserDeleted $event): void
    {
        $action = $event->forceDeleted ? 'force_deleted' : 'deleted';

        UserLifecycleAuditor::trail(
            $action,
            $event->actor,
            $event->user,
            UserLifecycleAuditor::snapshot($event->user),
            null,
            $event->forceDeleted ? 'User permanently deleted' : 'User archived'
        );
    }

    public function handleUserRestored(UserRestored $event): void
    {
        UserLifecycleAuditor::trail(
            'restored',
            $event->actor,
            $event->user,
            null,
            UserLifecycleAuditor::snapshot($event->user),
            'User restored'
        );
    }

    public function handleAvatarUpdated(AvatarUpdated $event): void
    {
        UserLifecycleAuditor::trail(
            'avatar_changed',
            $event->actor,
            $event->user,
            ['avatar' => $event->previousAvatar],
            ['avatar' => $event->avatar],
            'User avatar changed'
        );
    }
}

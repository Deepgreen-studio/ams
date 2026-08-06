<?php

namespace App\Domains\Users\Listeners;

use App\Domains\Users\Events\AvatarUpdated;
use App\Domains\Users\Events\UserCreated;
use App\Domains\Users\Events\UserDeleted;
use App\Domains\Users\Events\UserRestored;
use App\Domains\Users\Events\UserUpdated;

/**
 * Persists user-management audit trail via Spatie Activity Log.
 * Additional notification listeners can subscribe to the same events later.
 */
class LogUserActivity
{
    public function handleUserCreated(UserCreated $event): void
    {
        activity('users')
            ->causedBy($event->actor)
            ->performedOn($event->user)
            ->withProperties([
                'event' => 'user_created',
                'email' => $event->user->email,
                'status' => $event->user->status?->value ?? $event->user->status,
            ])
            ->log('User created');
    }

    public function handleUserUpdated(UserUpdated $event): void
    {
        $description = $event->context === 'profile_updated'
            ? 'Profile updated'
            : 'User updated';

        activity('users')
            ->causedBy($event->actor)
            ->performedOn($event->user)
            ->withProperties([
                'event' => $event->context,
                'email' => $event->user->email,
                'status' => $event->user->status?->value ?? $event->user->status,
            ])
            ->log($description);
    }

    public function handleUserDeleted(UserDeleted $event): void
    {
        activity('users')
            ->causedBy($event->actor)
            ->performedOn($event->user)
            ->withProperties([
                'event' => $event->forceDeleted ? 'user_force_deleted' : 'user_deleted',
                'email' => $event->user->email,
                'force_deleted' => $event->forceDeleted,
            ])
            ->log($event->forceDeleted ? 'User permanently deleted' : 'User deleted');
    }

    public function handleUserRestored(UserRestored $event): void
    {
        activity('users')
            ->causedBy($event->actor)
            ->performedOn($event->user)
            ->withProperties([
                'event' => 'user_restored',
                'email' => $event->user->email,
            ])
            ->log('User restored');
    }

    public function handleAvatarUpdated(AvatarUpdated $event): void
    {
        activity('users')
            ->causedBy($event->actor)
            ->performedOn($event->user)
            ->withProperties([
                'event' => 'avatar_changed',
                'previous_avatar' => $event->previousAvatar,
                'avatar' => $event->avatar,
            ])
            ->log('Avatar changed');
    }
}

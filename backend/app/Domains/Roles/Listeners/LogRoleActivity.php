<?php

namespace App\Domains\Roles\Listeners;

use App\Domains\Roles\Events\PermissionAssigned;
use App\Domains\Roles\Events\PermissionRemoved;
use App\Domains\Roles\Events\RoleCreated;
use App\Domains\Roles\Events\RoleDeleted;
use App\Domains\Roles\Events\RoleUpdated;
use App\Domains\Roles\Events\UserRoleAssigned;
use App\Domains\Roles\Events\UserRoleRemoved;

class LogRoleActivity
{
    public function handleRoleCreated(RoleCreated $event): void
    {
        activity('roles')
            ->causedBy($event->actor)
            ->performedOn($event->role)
            ->withProperties(['event' => 'role_created', 'name' => $event->role->name])
            ->log('Role created');
    }

    public function handleRoleUpdated(RoleUpdated $event): void
    {
        activity('roles')
            ->causedBy($event->actor)
            ->performedOn($event->role)
            ->withProperties(['event' => 'role_updated', 'name' => $event->role->name])
            ->log('Role updated');
    }

    public function handleRoleDeleted(RoleDeleted $event): void
    {
        activity('roles')
            ->causedBy($event->actor)
            ->performedOn($event->role)
            ->withProperties(['event' => 'role_deleted', 'name' => $event->role->name])
            ->log('Role deleted');
    }

    public function handlePermissionAssigned(PermissionAssigned $event): void
    {
        activity('roles')
            ->causedBy($event->actor)
            ->performedOn($event->role)
            ->withProperties([
                'event' => 'permission_assigned',
                'permission' => $event->permission,
            ])
            ->log('Permission assigned');
    }

    public function handlePermissionRemoved(PermissionRemoved $event): void
    {
        activity('roles')
            ->causedBy($event->actor)
            ->performedOn($event->role)
            ->withProperties([
                'event' => 'permission_removed',
                'permission' => $event->permission,
            ])
            ->log('Permission removed');
    }

    public function handleUserRoleAssigned(UserRoleAssigned $event): void
    {
        activity('roles')
            ->causedBy($event->actor)
            ->performedOn($event->user)
            ->withProperties([
                'event' => 'user_role_assigned',
                'role' => $event->role,
            ])
            ->log('User role assigned');
    }

    public function handleUserRoleRemoved(UserRoleRemoved $event): void
    {
        activity('roles')
            ->causedBy($event->actor)
            ->performedOn($event->user)
            ->withProperties([
                'event' => 'user_role_removed',
                'role' => $event->role,
            ])
            ->log('User role removed');
    }
}

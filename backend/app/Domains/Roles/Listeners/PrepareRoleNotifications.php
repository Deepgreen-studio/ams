<?php

namespace App\Domains\Roles\Listeners;

use App\Domains\Roles\Events\PermissionAssigned;
use App\Domains\Roles\Events\PermissionRemoved;
use App\Domains\Roles\Events\RoleCreated;
use App\Domains\Roles\Events\RoleDeleted;
use App\Domains\Roles\Events\RoleUpdated;
use App\Domains\Roles\Events\UserRoleAssigned;
use App\Domains\Roles\Events\UserRoleRemoved;

/**
 * Placeholder for future email / in-app notification workflows.
 */
class PrepareRoleNotifications
{
    public function handleRoleCreated(RoleCreated $event): void {}

    public function handleRoleUpdated(RoleUpdated $event): void {}

    public function handleRoleDeleted(RoleDeleted $event): void {}

    public function handlePermissionAssigned(PermissionAssigned $event): void {}

    public function handlePermissionRemoved(PermissionRemoved $event): void {}

    public function handleUserRoleAssigned(UserRoleAssigned $event): void {}

    public function handleUserRoleRemoved(UserRoleRemoved $event): void {}
}

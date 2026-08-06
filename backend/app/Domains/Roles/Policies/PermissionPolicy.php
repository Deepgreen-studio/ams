<?php

namespace App\Domains\Roles\Policies;

use App\Domains\Roles\Enums\RolePermission;
use App\Domains\Roles\Models\Permission;
use App\Models\User;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(RolePermission::VIEW);
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->can(RolePermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(RolePermission::CREATE);
    }
}

<?php

namespace App\Domains\Roles\Policies;

use App\Domains\Roles\Enums\RolePermission;
use App\Domains\Roles\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(RolePermission::VIEW);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can(RolePermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(RolePermission::CREATE);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can(RolePermission::UPDATE);
    }

    public function delete(User $user, Role $role): bool
    {
        if ($role->is_system) {
            return false;
        }

        return $user->can(RolePermission::DELETE);
    }

    public function restore(User $user, Role $role): bool
    {
        return $user->can(RolePermission::RESTORE);
    }

    public function forceDelete(User $user, Role $role): bool
    {
        if ($role->is_system) {
            return false;
        }

        return $user->can(RolePermission::FORCE_DELETE);
    }

    public function assignPermissions(User $user, Role $role): bool
    {
        return $user->can(RolePermission::ASSIGN);
    }

    public function assignToUser(User $user): bool
    {
        return $user->can(RolePermission::ASSIGN_ROLES) || $user->can(RolePermission::ASSIGN_USERS);
    }

    public function viewMatrix(User $user): bool
    {
        return $user->can(RolePermission::MATRIX);
    }

    public function viewTrash(User $user): bool
    {
        return $user->can(RolePermission::VIEW_TRASH);
    }

    public function viewPermissions(User $user): bool
    {
        return $user->can(RolePermission::VIEW)
            || $user->can(RolePermission::ASSIGN)
            || $user->can(RolePermission::MATRIX);
    }
}

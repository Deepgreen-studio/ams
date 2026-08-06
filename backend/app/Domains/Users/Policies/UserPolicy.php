<?php

namespace App\Domains\Users\Policies;

use App\Domains\Users\Enums\UserPermission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(UserPermission::VIEW);
    }

    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->can(UserPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(UserPermission::CREATE);
    }

    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->can(UserPermission::UPDATE);
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can(UserPermission::DELETE);
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can(UserPermission::RESTORE)
            || $user->can(UserPermission::DELETE);
    }

    public function forceDelete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can(UserPermission::FORCE_DELETE);
    }

    public function updateProfile(User $user, User $model): bool
    {
        return $user->id === $model->id && $user->isAccountActive();
    }

    public function uploadAvatar(User $user, User $model): bool
    {
        return $user->id === $model->id && $user->isAccountActive();
    }
}

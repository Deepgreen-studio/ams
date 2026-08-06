<?php

namespace App\Domains\Applications\Policies;

use App\Domains\Applications\Enums\ApplicationPermission;
use App\Domains\Applications\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(ApplicationPermission::VIEW);
    }

    public function view(User $user, Application $application): bool
    {
        return $user->can(ApplicationPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(ApplicationPermission::CREATE);
    }

    public function update(User $user, Application $application): bool
    {
        return $user->can(ApplicationPermission::UPDATE);
    }

    public function delete(User $user, Application $application): bool
    {
        return $user->can(ApplicationPermission::DELETE);
    }

    public function restore(User $user, Application $application): bool
    {
        return $user->can(ApplicationPermission::DELETE);
    }
}

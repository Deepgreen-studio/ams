<?php

namespace App\Domains\Applications\Policies;

use App\Domains\Applications\Enums\ApplicationPermission;
use App\Domains\Companies\Concerns\ChecksCompanyMembership;
use App\Domains\Applications\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    use ChecksCompanyMembership;

    public function viewAny(User $user): bool
    {
        return $user->can(ApplicationPermission::VIEW);
    }

    public function view(User $user, Application $application): bool
    {
        return $user->can(ApplicationPermission::VIEW) && $this->inCompany($user, $application);
    }

    public function create(User $user): bool
    {
        return $user->can(ApplicationPermission::CREATE);
    }

    public function update(User $user, Application $application): bool
    {
        return $user->can(ApplicationPermission::UPDATE) && $this->inCompany($user, $application);
    }

    public function delete(User $user, Application $application): bool
    {
        return $user->can(ApplicationPermission::DELETE) && $this->inCompany($user, $application);
    }

    public function restore(User $user, Application $application): bool
    {
        return $user->can(ApplicationPermission::RESTORE) && $this->inCompany($user, $application);
    }

    public function viewTrash(User $user): bool
    {
        return $user->can(ApplicationPermission::VIEW_TRASH);
    }
}

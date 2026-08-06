<?php

namespace App\Domains\Companies\Policies;

use App\Domains\Companies\Enums\CompanyPermission;
use App\Domains\Companies\Models\Company;
use App\Domains\Companies\Models\CompanyLocation;
use App\Domains\Companies\Models\Department;
use App\Domains\Companies\Models\Team;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(CompanyPermission::VIEW);
    }

    public function view(User $user, Company $company): bool
    {
        return $user->can(CompanyPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(CompanyPermission::CREATE);
    }

    public function update(User $user, Company $company): bool
    {
        return $user->can(CompanyPermission::UPDATE);
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->can(CompanyPermission::DELETE);
    }

    public function restore(User $user, Company $company): bool
    {
        return $user->can(CompanyPermission::RESTORE) || $user->can(CompanyPermission::DELETE);
    }

    public function manageBranding(User $user, Company $company): bool
    {
        return $user->can(CompanyPermission::MANAGE) || $user->can(CompanyPermission::UPDATE);
    }

    public function manageDepartments(User $user): bool
    {
        return $user->can(CompanyPermission::UPDATE);
    }

    public function viewDepartments(User $user): bool
    {
        return $user->can(CompanyPermission::VIEW);
    }

    public function manageTeams(User $user): bool
    {
        return $user->can(CompanyPermission::UPDATE);
    }

    public function viewTeams(User $user): bool
    {
        return $user->can(CompanyPermission::VIEW);
    }

    public function manageLocations(User $user): bool
    {
        return $user->can(CompanyPermission::UPDATE);
    }

    public function viewLocations(User $user): bool
    {
        return $user->can(CompanyPermission::VIEW);
    }

    public function updateDepartment(User $user, Department $department): bool
    {
        return $this->manageDepartments($user);
    }

    public function deleteDepartment(User $user, Department $department): bool
    {
        return $user->can(CompanyPermission::DELETE) || $user->can(CompanyPermission::UPDATE);
    }

    public function updateTeam(User $user, Team $team): bool
    {
        return $this->manageTeams($user);
    }

    public function deleteTeam(User $user, Team $team): bool
    {
        return $user->can(CompanyPermission::DELETE) || $user->can(CompanyPermission::UPDATE);
    }

    public function updateLocation(User $user, CompanyLocation $location): bool
    {
        return $this->manageLocations($user);
    }

    public function deleteLocation(User $user, CompanyLocation $location): bool
    {
        return $user->can(CompanyPermission::DELETE) || $user->can(CompanyPermission::UPDATE);
    }
}

<?php

namespace App\Domains\Companies\Policies;

use App\Domains\Companies\Enums\CompanyPermission;
use App\Domains\Companies\Enums\DepartmentPermission;
use App\Domains\Companies\Enums\LocationPermission;
use App\Domains\Companies\Enums\TeamPermission;
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
        return $user->can(DepartmentPermission::CREATE);
    }

    public function viewDepartments(User $user): bool
    {
        return $user->can(DepartmentPermission::VIEW);
    }

    public function viewDepartment(User $user, Department $department): bool
    {
        return $this->viewDepartments($user);
    }

    public function manageTeams(User $user): bool
    {
        return $user->can(TeamPermission::CREATE);
    }

    public function viewTeams(User $user): bool
    {
        return $user->can(TeamPermission::VIEW);
    }

    public function manageLocations(User $user): bool
    {
        return $user->can(LocationPermission::CREATE);
    }

    public function viewLocations(User $user): bool
    {
        return $user->can(LocationPermission::VIEW);
    }

    public function updateDepartment(User $user, Department $department): bool
    {
        return $user->can(DepartmentPermission::UPDATE);
    }

    public function deleteDepartment(User $user, Department $department): bool
    {
        return $user->can(DepartmentPermission::DELETE);
    }

    public function updateTeam(User $user, Team $team): bool
    {
        return $user->can(TeamPermission::UPDATE);
    }

    public function deleteTeam(User $user, Team $team): bool
    {
        return $user->can(TeamPermission::DELETE);
    }

    public function updateLocation(User $user, CompanyLocation $location): bool
    {
        return $user->can(LocationPermission::UPDATE);
    }

    public function deleteLocation(User $user, CompanyLocation $location): bool
    {
        return $user->can(LocationPermission::DELETE);
    }
}

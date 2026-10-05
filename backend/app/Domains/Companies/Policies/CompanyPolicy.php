<?php

namespace App\Domains\Companies\Policies;

use App\Domains\Companies\Concerns\ChecksCompanyMembership;
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
    use ChecksCompanyMembership;

    public function viewAny(User $user): bool
    {
        return $user->can(CompanyPermission::VIEW);
    }

    public function view(User $user, Company $company): bool
    {
        return $user->can(CompanyPermission::VIEW) && $this->inCompany($user, $company);
    }

    public function create(User $user): bool
    {
        return $user->can(CompanyPermission::CREATE);
    }

    public function update(User $user, Company $company): bool
    {
        return $user->can(CompanyPermission::UPDATE) && $this->inCompany($user, $company);
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->can(CompanyPermission::DELETE) && $this->inCompany($user, $company);
    }

    public function restore(User $user, Company $company): bool
    {
        return $user->can(CompanyPermission::RESTORE) && $this->inCompany($user, $company);
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return $user->can(CompanyPermission::FORCE_DELETE) && $this->inCompany($user, $company);
    }

    public function viewTrash(User $user): bool
    {
        return $user->can(CompanyPermission::VIEW_TRASH);
    }

    public function viewConsole(User $user, Company $company): bool
    {
        return $user->can(CompanyPermission::CONSOLE) && $this->inCompany($user, $company);
    }

    public function viewProfile(User $user, Company $company): bool
    {
        return $user->can(CompanyPermission::PROFILE) && $this->inCompany($user, $company);
    }

    public function manageBranding(User $user, Company $company): bool
    {
        return ($user->can(CompanyPermission::MANAGE)
            || $user->can(CompanyPermission::UPDATE)
            || $user->can(CompanyPermission::PROFILE))
            && $this->inCompany($user, $company);
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
        return $this->viewDepartments($user) && $this->inCompany($user, $department);
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
        return $user->can(DepartmentPermission::UPDATE) && $this->inCompany($user, $department);
    }

    public function deleteDepartment(User $user, Department $department): bool
    {
        return $user->can(DepartmentPermission::DELETE) && $this->inCompany($user, $department);
    }

    public function updateTeam(User $user, Team $team): bool
    {
        return $user->can(TeamPermission::UPDATE) && $this->inCompany($user, $team);
    }

    public function deleteTeam(User $user, Team $team): bool
    {
        return $user->can(TeamPermission::DELETE) && $this->inCompany($user, $team);
    }

    public function updateLocation(User $user, CompanyLocation $location): bool
    {
        return $user->can(LocationPermission::UPDATE) && $this->inCompany($user, $location);
    }

    public function deleteLocation(User $user, CompanyLocation $location): bool
    {
        return $user->can(LocationPermission::DELETE) && $this->inCompany($user, $location);
    }
}

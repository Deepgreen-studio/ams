<?php

namespace App\Domains\Compliance\Policies;

use App\Domains\Compliance\Enums\CompliancePermission;
use App\Domains\Compliance\Models\ComplianceCase;
use App\Models\User;

class ComplianceCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(CompliancePermission::VIEW);
    }

    public function view(User $user, ComplianceCase $case): bool
    {
        return $user->can(CompliancePermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(CompliancePermission::CREATE);
    }

    public function update(User $user, ComplianceCase $case): bool
    {
        return $user->can(CompliancePermission::UPDATE) || $user->can(CompliancePermission::MANAGE);
    }

    public function delete(User $user, ComplianceCase $case): bool
    {
        return $user->can(CompliancePermission::DELETE) || $user->can(CompliancePermission::MANAGE);
    }

    public function restore(User $user, ComplianceCase $case): bool
    {
        return $user->can(CompliancePermission::DELETE) || $user->can(CompliancePermission::MANAGE);
    }

    public function manage(User $user): bool
    {
        return $user->can(CompliancePermission::MANAGE);
    }
}

<?php

namespace App\Domains\Compliance\Policies;

use App\Domains\Compliance\Enums\CompliancePermission;
use App\Domains\Compliance\Models\ConsentType;
use App\Domains\Compliance\Models\UserConsent;
use App\Models\User;

class ConsentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(CompliancePermission::VIEW);
    }

    public function view(User $user, UserConsent|ConsentType $model): bool
    {
        return $user->can(CompliancePermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(CompliancePermission::CREATE) || $user->can(CompliancePermission::MANAGE);
    }

    public function update(User $user, UserConsent|ConsentType $model): bool
    {
        return $user->can(CompliancePermission::UPDATE) || $user->can(CompliancePermission::MANAGE);
    }

    public function delete(User $user, UserConsent|ConsentType $model): bool
    {
        return $user->can(CompliancePermission::DELETE) || $user->can(CompliancePermission::MANAGE);
    }

    public function manage(User $user): bool
    {
        return $user->can(CompliancePermission::MANAGE);
    }
}

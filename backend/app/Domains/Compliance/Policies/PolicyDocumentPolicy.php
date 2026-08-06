<?php

namespace App\Domains\Compliance\Policies;

use App\Domains\Compliance\Enums\CompliancePermission;
use App\Domains\Compliance\Models\PolicyDocument;
use App\Models\User;

class PolicyDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(CompliancePermission::VIEW);
    }

    public function view(User $user, PolicyDocument $policyDocument): bool
    {
        return $user->can(CompliancePermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(CompliancePermission::CREATE);
    }

    public function update(User $user, PolicyDocument $policyDocument): bool
    {
        return $user->can(CompliancePermission::UPDATE) || $user->can(CompliancePermission::MANAGE);
    }

    public function delete(User $user, PolicyDocument $policyDocument): bool
    {
        return $user->can(CompliancePermission::DELETE) || $user->can(CompliancePermission::MANAGE);
    }

    public function approve(User $user): bool
    {
        return $user->can(CompliancePermission::UPDATE) || $user->can(CompliancePermission::MANAGE);
    }

    public function publish(User $user, PolicyDocument $policyDocument): bool
    {
        return $user->can(CompliancePermission::UPDATE) || $user->can(CompliancePermission::MANAGE);
    }

    public function manage(User $user): bool
    {
        return $user->can(CompliancePermission::MANAGE);
    }
}

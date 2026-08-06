<?php

namespace App\Domains\Compliance\Policies;

use App\Domains\Compliance\Enums\CompliancePermission;
use App\Domains\Compliance\Models\PrivacyRequest;
use App\Models\User;

class PrivacyRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(CompliancePermission::VIEW);
    }

    public function view(User $user, PrivacyRequest $privacyRequest): bool
    {
        return $user->can(CompliancePermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(CompliancePermission::CREATE);
    }

    public function update(User $user, PrivacyRequest $privacyRequest): bool
    {
        return $user->can(CompliancePermission::UPDATE) || $user->can(CompliancePermission::MANAGE);
    }

    public function delete(User $user, PrivacyRequest $privacyRequest): bool
    {
        return $user->can(CompliancePermission::DELETE) || $user->can(CompliancePermission::MANAGE);
    }

    public function restore(User $user, PrivacyRequest $privacyRequest): bool
    {
        return $user->can(CompliancePermission::DELETE) || $user->can(CompliancePermission::MANAGE);
    }

    public function verify(User $user, PrivacyRequest $privacyRequest): bool
    {
        return $user->can(CompliancePermission::UPDATE) || $user->can(CompliancePermission::MANAGE);
    }

    public function decide(User $user, PrivacyRequest $privacyRequest): bool
    {
        return $user->can(CompliancePermission::UPDATE) || $user->can(CompliancePermission::MANAGE);
    }

    public function export(User $user, PrivacyRequest $privacyRequest): bool
    {
        return $user->can(CompliancePermission::UPDATE) || $user->can(CompliancePermission::MANAGE);
    }

    public function manage(User $user): bool
    {
        return $user->can(CompliancePermission::MANAGE);
    }
}

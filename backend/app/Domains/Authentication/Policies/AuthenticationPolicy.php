<?php

namespace App\Domains\Authentication\Policies;

use App\Models\User;

/**
 * Authentication policy placeholder for future authz rules
 * (e.g. restricting password changes for locked accounts).
 */
class AuthenticationPolicy
{
    public function changePassword(User $user): bool
    {
        return $user->is_active;
    }

    public function logoutAllDevices(User $user): bool
    {
        return $user->is_active;
    }
}

<?php

namespace App\Domains\Users\Requests;

use App\Domains\Users\Enums\UserPermission;
use Illuminate\Auth\Access\AuthorizationException;

trait AuthorizesRoleAssignment
{
    public function authorize(): bool
    {
        if (! $this->exists('roles')) {
            return true;
        }

        return (bool) $this->user()?->can(UserPermission::ASSIGN_ROLES);
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('You are not allowed to assign roles to users.');
    }
}

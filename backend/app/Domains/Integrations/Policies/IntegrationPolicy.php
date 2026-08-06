<?php

namespace App\Domains\Integrations\Policies;

use App\Domains\Integrations\Enums\IntegrationPermission;
use App\Domains\Integrations\Models\Integration;
use App\Models\User;

class IntegrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(IntegrationPermission::VIEW);
    }

    public function view(User $user, Integration $integration): bool
    {
        return $user->can(IntegrationPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(IntegrationPermission::CREATE);
    }

    public function update(User $user, Integration $integration): bool
    {
        return $user->can(IntegrationPermission::UPDATE);
    }

    public function delete(User $user, Integration $integration): bool
    {
        return $user->can(IntegrationPermission::DELETE);
    }

    public function restore(User $user, Integration $integration): bool
    {
        return $user->can(IntegrationPermission::MANAGE) || $user->can(IntegrationPermission::DELETE);
    }

    public function manage(User $user, Integration $integration): bool
    {
        return $user->can(IntegrationPermission::MANAGE);
    }
}

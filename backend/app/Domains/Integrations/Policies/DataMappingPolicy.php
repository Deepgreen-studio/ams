<?php

namespace App\Domains\Integrations\Policies;

use App\Domains\Integrations\Enums\IntegrationPermission;
use App\Domains\Integrations\Models\DataMapping;
use App\Models\User;

class DataMappingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(IntegrationPermission::VIEW);
    }

    public function view(User $user, DataMapping $mapping): bool
    {
        return $user->can(IntegrationPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(IntegrationPermission::CREATE);
    }

    public function update(User $user, DataMapping $mapping): bool
    {
        return $user->can(IntegrationPermission::UPDATE);
    }

    public function delete(User $user, DataMapping $mapping): bool
    {
        return $user->can(IntegrationPermission::DELETE);
    }

    public function preview(User $user, DataMapping $mapping): bool
    {
        return $user->can(IntegrationPermission::MANAGE) || $user->can(IntegrationPermission::VIEW);
    }
}

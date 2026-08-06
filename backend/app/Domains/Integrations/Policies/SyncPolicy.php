<?php

namespace App\Domains\Integrations\Policies;

use App\Domains\Integrations\Enums\IntegrationPermission;
use App\Domains\Integrations\Models\SyncConfig;
use App\Domains\Integrations\Models\SyncRun;
use App\Models\User;

class SyncPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(IntegrationPermission::VIEW);
    }

    public function view(User $user, SyncConfig|SyncRun $model): bool
    {
        return $user->can(IntegrationPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(IntegrationPermission::CREATE);
    }

    public function update(User $user, SyncConfig $config): bool
    {
        return $user->can(IntegrationPermission::UPDATE);
    }

    public function delete(User $user, SyncConfig $config): bool
    {
        return $user->can(IntegrationPermission::DELETE);
    }

    public function run(User $user, SyncConfig $config): bool
    {
        return $user->can(IntegrationPermission::MANAGE);
    }
}

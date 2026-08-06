<?php

namespace App\Domains\Monitoring\Policies;

use App\Domains\Monitoring\Enums\MonitoringPermission;
use App\Domains\Monitoring\Models\MonitoringAlert;
use App\Domains\Monitoring\Models\MonitoringSnapshot;
use App\Models\User;

class MonitoringPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(MonitoringPermission::VIEW);
    }

    public function view(User $user, MonitoringAlert|MonitoringSnapshot $model): bool
    {
        return $user->can(MonitoringPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(MonitoringPermission::MANAGE);
    }

    public function update(User $user, MonitoringAlert $alert): bool
    {
        return $user->can(MonitoringPermission::MANAGE);
    }

    public function delete(User $user, MonitoringAlert $alert): bool
    {
        return $user->can(MonitoringPermission::MANAGE);
    }

    public function manage(User $user): bool
    {
        return $user->can(MonitoringPermission::MANAGE);
    }
}

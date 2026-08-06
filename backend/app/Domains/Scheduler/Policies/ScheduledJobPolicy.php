<?php

namespace App\Domains\Scheduler\Policies;

use App\Domains\Scheduler\Enums\SchedulerPermission;
use App\Domains\Scheduler\Models\ScheduledJob;
use App\Models\User;

class ScheduledJobPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(SchedulerPermission::VIEW);
    }

    public function view(User $user, ScheduledJob $job): bool
    {
        return $user->can(SchedulerPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(SchedulerPermission::CREATE);
    }

    public function update(User $user, ScheduledJob $job): bool
    {
        return $user->can(SchedulerPermission::UPDATE) || $user->can(SchedulerPermission::MANAGE);
    }

    public function delete(User $user, ScheduledJob $job): bool
    {
        return $user->can(SchedulerPermission::DELETE) || $user->can(SchedulerPermission::MANAGE);
    }

    public function manage(User $user): bool
    {
        return $user->can(SchedulerPermission::MANAGE);
    }

    public function retry(User $user): bool
    {
        return $user->can(SchedulerPermission::RETRY) || $user->can(SchedulerPermission::MANAGE);
    }
}

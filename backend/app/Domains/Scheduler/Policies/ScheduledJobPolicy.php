<?php

namespace App\Domains\Scheduler\Policies;

use App\Domains\Companies\Concerns\ChecksCompanyMembership;
use App\Domains\Scheduler\Enums\SchedulerPermission;
use App\Domains\Scheduler\Models\ScheduledJob;
use App\Models\User;

class ScheduledJobPolicy
{
    use ChecksCompanyMembership;

    public function viewAny(User $user): bool
    {
        return $user->can(SchedulerPermission::VIEW);
    }

    public function view(User $user, ScheduledJob $job): bool
    {
        return $user->can(SchedulerPermission::VIEW) && $this->inCompany($user, $job);
    }

    public function create(User $user): bool
    {
        return $user->can(SchedulerPermission::CREATE);
    }

    public function update(User $user, ScheduledJob $job): bool
    {
        return ($user->can(SchedulerPermission::UPDATE) || $user->can(SchedulerPermission::MANAGE))
            && $this->inCompany($user, $job);
    }

    public function delete(User $user, ScheduledJob $job): bool
    {
        return ($user->can(SchedulerPermission::DELETE) || $user->can(SchedulerPermission::MANAGE))
            && $this->inCompany($user, $job);
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

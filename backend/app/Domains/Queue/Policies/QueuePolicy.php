<?php

namespace App\Domains\Queue\Policies;

use App\Domains\Queue\Enums\QueuePermission;
use App\Domains\Queue\Models\QueueJobTrack;
use App\Models\User;

class QueuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(QueuePermission::VIEW);
    }

    public function view(User $user, QueueJobTrack $track): bool
    {
        return $user->can(QueuePermission::VIEW);
    }

    public function manage(User $user): bool
    {
        return $user->can(QueuePermission::MANAGE);
    }

    public function retry(User $user): bool
    {
        return $user->can(QueuePermission::RETRY) || $user->can(QueuePermission::MANAGE);
    }
}

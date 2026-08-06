<?php

namespace App\Domains\Compliance\Policies;

use App\Domains\Compliance\Enums\CompliancePermission;
use App\Domains\Compliance\Models\DataBreach;
use App\Models\User;

class DataBreachPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(CompliancePermission::VIEW);
    }

    public function view(User $user, DataBreach $dataBreach): bool
    {
        return $user->can(CompliancePermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(CompliancePermission::CREATE);
    }

    public function update(User $user, DataBreach $dataBreach): bool
    {
        return $user->can(CompliancePermission::UPDATE) || $user->can(CompliancePermission::MANAGE);
    }

    public function delete(User $user, DataBreach $dataBreach): bool
    {
        return $user->can(CompliancePermission::DELETE) || $user->can(CompliancePermission::MANAGE);
    }

    public function restore(User $user, DataBreach $dataBreach): bool
    {
        return $user->can(CompliancePermission::DELETE) || $user->can(CompliancePermission::MANAGE);
    }

    public function assess(User $user, DataBreach $dataBreach): bool
    {
        return $user->can(CompliancePermission::UPDATE) || $user->can(CompliancePermission::MANAGE);
    }

    public function contain(User $user, DataBreach $dataBreach): bool
    {
        return $user->can(CompliancePermission::UPDATE) || $user->can(CompliancePermission::MANAGE);
    }

    public function notify(User $user, DataBreach $dataBreach): bool
    {
        return $user->can(CompliancePermission::UPDATE) || $user->can(CompliancePermission::MANAGE);
    }

    public function close(User $user, DataBreach $dataBreach): bool
    {
        return $user->can(CompliancePermission::UPDATE) || $user->can(CompliancePermission::MANAGE);
    }

    public function manage(User $user): bool
    {
        return $user->can(CompliancePermission::MANAGE);
    }
}

<?php

namespace App\Domains\Workflows\Policies;

use App\Domains\Workflows\Enums\WorkflowPermission;
use App\Domains\Workflows\Models\Workflow;
use App\Domains\Workflows\Models\WorkflowInstance;
use App\Models\User;

class WorkflowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(WorkflowPermission::VIEW);
    }

    public function view(User $user, Workflow $workflow): bool
    {
        return $user->can(WorkflowPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(WorkflowPermission::CREATE);
    }

    public function update(User $user, Workflow $workflow): bool
    {
        return $user->can(WorkflowPermission::UPDATE) || $user->can(WorkflowPermission::MANAGE);
    }

    public function delete(User $user, Workflow $workflow): bool
    {
        return $user->can(WorkflowPermission::DELETE) || $user->can(WorkflowPermission::MANAGE);
    }

    public function manage(User $user): bool
    {
        return $user->can(WorkflowPermission::MANAGE);
    }

    public function approve(User $user, ?WorkflowInstance $instance = null): bool
    {
        return $user->can(WorkflowPermission::APPROVE)
            || $user->can(WorkflowPermission::MANAGE);
    }
}

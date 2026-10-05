<?php

namespace App\Domains\Automation\Policies;

use App\Domains\Automation\Enums\AutomationPermission;
use App\Domains\Companies\Concerns\ChecksCompanyMembership;
use App\Domains\Automation\Models\AutomationRule;
use App\Models\User;

class AutomationRulePolicy
{
    use ChecksCompanyMembership;

    public function viewAny(User $user): bool
    {
        return $user->can(AutomationPermission::VIEW);
    }

    public function view(User $user, AutomationRule $rule): bool
    {
        return $user->can(AutomationPermission::VIEW) && $this->inCompany($user, $rule);
    }

    public function create(User $user): bool
    {
        return $user->can(AutomationPermission::CREATE);
    }

    public function update(User $user, AutomationRule $rule): bool
    {
        return ($user->can(AutomationPermission::UPDATE) || $user->can(AutomationPermission::MANAGE))
            && $this->inCompany($user, $rule);
    }

    public function delete(User $user, AutomationRule $rule): bool
    {
        return ($user->can(AutomationPermission::DELETE) || $user->can(AutomationPermission::MANAGE))
            && $this->inCompany($user, $rule);
    }

    public function manage(User $user): bool
    {
        return $user->can(AutomationPermission::MANAGE);
    }
}

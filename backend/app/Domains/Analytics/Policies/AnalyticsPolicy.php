<?php

namespace App\Domains\Analytics\Policies;

use App\Domains\Analytics\Enums\AnalyticsPermission;
use App\Domains\Analytics\Models\AnalyticsDashboard;
use App\Models\User;

class AnalyticsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(AnalyticsPermission::VIEW);
    }

    public function view(User $user, mixed $model = null): bool
    {
        return $user->can(AnalyticsPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(AnalyticsPermission::CREATE)
            || $user->can(AnalyticsPermission::MANAGE);
    }

    public function update(User $user, mixed $model = null): bool
    {
        return $user->can(AnalyticsPermission::UPDATE)
            || $user->can(AnalyticsPermission::MANAGE);
    }

    public function delete(User $user, mixed $model = null): bool
    {
        if ($model instanceof AnalyticsDashboard && $model->is_system) {
            return false;
        }

        return $user->can(AnalyticsPermission::DELETE)
            || $user->can(AnalyticsPermission::MANAGE);
    }

    public function export(User $user, mixed $subject = null): bool
    {
        return $user->can(AnalyticsPermission::EXPORT)
            || $user->can(AnalyticsPermission::VIEW);
    }

    public function manage(User $user): bool
    {
        return $user->can(AnalyticsPermission::MANAGE);
    }

    public function record(User $user): bool
    {
        return $user->can(AnalyticsPermission::CREATE)
            || $user->can(AnalyticsPermission::MANAGE);
    }
}

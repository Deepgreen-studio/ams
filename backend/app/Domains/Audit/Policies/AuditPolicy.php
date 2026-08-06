<?php

namespace App\Domains\Audit\Policies;

use App\Domains\Audit\Enums\AuditPermission;
use App\Domains\Audit\Models\ActivityLog;
use App\Domains\Audit\Models\ApiLog;
use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Models\ErrorLog;
use App\Domains\Audit\Models\SystemEvent;
use App\Domains\Users\Models\UserLoginHistory;
use App\Models\User;

class AuditPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(AuditPermission::VIEW);
    }

    public function view(User $user, ActivityLog|AuditLog|ApiLog|SystemEvent|ErrorLog|UserLoginHistory $model): bool
    {
        return $user->can(AuditPermission::VIEW);
    }

    public function export(User $user): bool
    {
        return $user->can(AuditPermission::EXPORT) || $user->can(AuditPermission::MANAGE);
    }

    public function viewErrors(User $user): bool
    {
        return $user->can(AuditPermission::VIEW) || $user->can(AuditPermission::MANAGE);
    }

    public function viewApiLogs(User $user): bool
    {
        return $user->can(AuditPermission::VIEW) || $user->can(AuditPermission::MANAGE);
    }
}

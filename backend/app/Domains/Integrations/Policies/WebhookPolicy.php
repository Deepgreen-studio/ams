<?php

namespace App\Domains\Integrations\Policies;

use App\Domains\Integrations\Enums\IntegrationPermission;
use App\Domains\Integrations\Models\Webhook;
use App\Domains\Integrations\Models\WebhookLog;
use App\Models\User;

class WebhookPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(IntegrationPermission::VIEW);
    }

    public function view(User $user, Webhook $webhook): bool
    {
        return $user->can(IntegrationPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(IntegrationPermission::CREATE);
    }

    public function update(User $user, Webhook $webhook): bool
    {
        return $user->can(IntegrationPermission::UPDATE);
    }

    public function delete(User $user, Webhook $webhook): bool
    {
        return $user->can(IntegrationPermission::DELETE);
    }

    public function manage(User $user, Webhook $webhook): bool
    {
        return $user->can(IntegrationPermission::MANAGE);
    }

    public function viewLog(User $user, WebhookLog $log): bool
    {
        return $user->can(IntegrationPermission::VIEW);
    }

    public function retry(User $user, WebhookLog $log): bool
    {
        return $user->can(IntegrationPermission::MANAGE);
    }
}

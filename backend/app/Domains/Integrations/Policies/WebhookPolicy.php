<?php

namespace App\Domains\Integrations\Policies;

use App\Domains\Integrations\Enums\WebhookPermission;
use App\Domains\Integrations\Models\Webhook;
use App\Domains\Integrations\Models\WebhookLog;
use App\Models\User;

class WebhookPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(WebhookPermission::VIEW);
    }

    public function view(User $user, Webhook $webhook): bool
    {
        return $user->can(WebhookPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(WebhookPermission::CREATE);
    }

    public function update(User $user, Webhook $webhook): bool
    {
        return $user->can(WebhookPermission::UPDATE);
    }

    public function delete(User $user, Webhook $webhook): bool
    {
        return $user->can(WebhookPermission::DELETE);
    }

    public function test(User $user, Webhook $webhook): bool
    {
        return $user->can(WebhookPermission::TEST);
    }

    public function viewLogs(User $user): bool
    {
        return $user->can(WebhookPermission::LOGS);
    }

    public function viewEvents(User $user): bool
    {
        return $user->can(WebhookPermission::EVENTS);
    }

    public function viewLog(User $user, WebhookLog $log): bool
    {
        return $user->can(WebhookPermission::LOGS);
    }

    public function retry(User $user, WebhookLog $log): bool
    {
        return $user->can(WebhookPermission::TEST);
    }
}

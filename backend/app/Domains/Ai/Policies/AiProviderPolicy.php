<?php

namespace App\Domains\Ai\Policies;

use App\Domains\Ai\Enums\AiPermission;
use App\Domains\Ai\Models\AiProvider;
use App\Models\User;

class AiProviderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(AiPermission::VIEW);
    }

    public function view(User $user, AiProvider $provider): bool
    {
        return $user->can(AiPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(AiPermission::CREATE) || $user->can(AiPermission::MANAGE);
    }

    public function update(User $user, AiProvider $provider): bool
    {
        return $user->can(AiPermission::UPDATE) || $user->can(AiPermission::MANAGE);
    }

    public function delete(User $user, AiProvider $provider): bool
    {
        return $user->can(AiPermission::DELETE) || $user->can(AiPermission::MANAGE);
    }

    public function manage(User $user): bool
    {
        return $user->can(AiPermission::MANAGE);
    }

    public function chat(User $user): bool
    {
        return $user->can(AiPermission::CHAT) || $user->can(AiPermission::MANAGE) || $user->can(AiPermission::VIEW);
    }
}

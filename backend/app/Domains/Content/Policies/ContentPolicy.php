<?php

namespace App\Domains\Content\Policies;

use App\Domains\Content\Enums\ContentPermission;
use App\Domains\Content\Models\Content;
use App\Models\User;

class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(ContentPermission::VIEW);
    }

    public function view(User $user, Content $content): bool
    {
        return $user->can(ContentPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(ContentPermission::CREATE);
    }

    public function update(User $user, Content $content): bool
    {
        return $user->can(ContentPermission::UPDATE);
    }

    public function delete(User $user, Content $content): bool
    {
        return $user->can(ContentPermission::DELETE);
    }

    public function restore(User $user, Content $content): bool
    {
        return $user->can(ContentPermission::DELETE);
    }

    public function publish(User $user, Content $content): bool
    {
        return $user->can(ContentPermission::PUBLISH);
    }
}

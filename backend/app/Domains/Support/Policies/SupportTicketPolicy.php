<?php

namespace App\Domains\Support\Policies;

use App\Domains\Support\Enums\SupportPermission;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(SupportPermission::VIEW);
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return $user->can(SupportPermission::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(SupportPermission::CREATE);
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return $user->can(SupportPermission::UPDATE) || $user->can(SupportPermission::MANAGE);
    }

    public function delete(User $user, SupportTicket $ticket): bool
    {
        return $user->can(SupportPermission::DELETE) || $user->can(SupportPermission::MANAGE);
    }

    public function restore(User $user, SupportTicket $ticket): bool
    {
        return $user->can(SupportPermission::DELETE) || $user->can(SupportPermission::MANAGE);
    }

    public function assign(User $user, SupportTicket $ticket): bool
    {
        return $user->can(SupportPermission::UPDATE) || $user->can(SupportPermission::MANAGE);
    }

    public function close(User $user, SupportTicket $ticket): bool
    {
        return $user->can(SupportPermission::UPDATE) || $user->can(SupportPermission::MANAGE);
    }

    public function reply(User $user, SupportTicket $ticket): bool
    {
        return $user->can(SupportPermission::UPDATE)
            || $user->can(SupportPermission::CREATE)
            || $user->can(SupportPermission::MANAGE);
    }

    public function manage(User $user): bool
    {
        return $user->can(SupportPermission::MANAGE);
    }
}

<?php

namespace App\Domains\Support\Services;

use App\Domains\Support\Enums\SupportPermission;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Collection;

class SupportNotificationRecipientResolver
{
    /**
     * Agents (assignee) + users with support.manage, excluding the actor.
     *
     * @return Collection<int, User>
     */
    public function forTicket(SupportTicket $ticket, ?User $actor = null): Collection
    {
        $ticket->loadMissing('assignee');

        $recipients = collect();

        if ($ticket->assignee instanceof User) {
            $recipients->push($ticket->assignee);
        }

        $managers = User::query()
            ->permission(SupportPermission::MANAGE)
            ->where('is_active', true)
            ->get();

        $recipients = $recipients->merge($managers);

        if ($ticket->customer_id) {
            $portalUsers = User::query()
                ->where('customer_id', $ticket->customer_id)
                ->where('is_active', true)
                ->get();
            $recipients = $recipients->merge($portalUsers);
        }

        $recipients = $recipients->unique('id');

        if ($actor) {
            $recipients = $recipients->reject(fn (User $user) => (int) $user->id === (int) $actor->id);
        }

        return $recipients->values();
    }
}

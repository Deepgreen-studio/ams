<?php

namespace App\Domains\Support\Events;

use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTicketAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly User $actor
    ) {}
}

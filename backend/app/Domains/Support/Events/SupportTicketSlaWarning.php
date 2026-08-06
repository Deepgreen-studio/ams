<?php

namespace App\Domains\Support\Events;

use App\Domains\Support\Models\SupportTicket;
use App\Models\User;

class SupportTicketSlaWarning
{
    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly string $metric,
        public readonly ?User $actor = null,
    ) {}
}

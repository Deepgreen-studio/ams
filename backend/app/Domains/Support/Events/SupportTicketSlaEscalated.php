<?php

namespace App\Domains\Support\Events;

use App\Domains\Support\Models\SupportSlaEscalation;
use App\Models\User;

class SupportTicketSlaEscalated
{
    public function __construct(
        public readonly SupportSlaEscalation $escalation,
        public readonly ?User $actor = null,
    ) {}
}

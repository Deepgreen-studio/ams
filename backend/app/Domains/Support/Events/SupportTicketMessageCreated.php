<?php

namespace App\Domains\Support\Events;

use App\Domains\Support\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTicketMessageCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SupportTicketMessage $message,
        public readonly User $actor
    ) {}
}

<?php

namespace App\Domains\Support\Events;

use App\Domains\Support\Models\SupportTicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTicketAttachmentUploaded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SupportTicketAttachment $attachment,
        public readonly User $actor
    ) {}
}

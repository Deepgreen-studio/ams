<?php

namespace App\Domains\Notifications\Events;

use App\Domains\Notifications\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationTemplateApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly NotificationTemplate $template,
        public readonly User $actor,
    ) {}
}

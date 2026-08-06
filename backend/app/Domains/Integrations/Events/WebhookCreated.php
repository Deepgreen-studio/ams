<?php

namespace App\Domains\Integrations\Events;

use App\Domains\Integrations\Models\Webhook;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Webhook $webhook, public readonly User $actor) {}
}

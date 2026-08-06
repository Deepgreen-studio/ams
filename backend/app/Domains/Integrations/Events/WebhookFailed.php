<?php

namespace App\Domains\Integrations\Events;

use App\Domains\Integrations\Models\Webhook;
use App\Domains\Integrations\Models\WebhookLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Webhook $webhook, public readonly WebhookLog $log) {}
}

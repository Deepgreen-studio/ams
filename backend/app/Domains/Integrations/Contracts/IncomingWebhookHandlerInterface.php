<?php

namespace App\Domains\Integrations\Contracts;

use App\Domains\Integrations\Models\Webhook;
use App\Domains\Integrations\Models\WebhookLog;
use App\Models\User;

interface IncomingWebhookHandlerInterface
{
    /**
     * Whether this handler owns the given incoming webhook.
     */
    public function supports(Webhook $webhook): bool;

    /**
     * Ingest a verified incoming webhook payload into AMS domains.
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     handled: bool,
     *     skipped?: bool,
     *     reason?: string,
     *     support_ticket_uuid?: string|null,
     *     support_ticket_number?: string|null,
     *     privacy_request_uuid?: string|null,
     *     privacy_request_number?: string|null,
     *     actions?: list<string>
     * }
     */
    public function handle(Webhook $webhook, WebhookLog $log, array $payload, User $actor): array;
}

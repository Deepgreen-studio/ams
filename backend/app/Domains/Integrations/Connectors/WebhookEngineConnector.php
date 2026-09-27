<?php

namespace App\Domains\Integrations\Connectors;

use App\Domains\Integrations\Enums\ConnectorCapability;
use App\Domains\Integrations\Models\Integration;

class WebhookEngineConnector extends AbstractConnector
{
    public function key(): string
    {
        return 'webhook-engine';
    }

    public function label(): string
    {
        return 'Webhook engine';
    }

    public function description(): string
    {
        return 'Incoming and outgoing webhooks, signature verification, and queued delivery retries through the webhook engine.';
    }

    public function capabilities(): array
    {
        return [
            ConnectorCapability::Webhook->value,
            ConnectorCapability::QueueRetry->value,
        ];
    }

    public function priority(): int
    {
        return 30;
    }

    public function matchesIntegration(?Integration $integration): bool
    {
        if ($integration === null) {
            return false;
        }

        $type = $integration->type?->value ?? (string) $integration->type;

        return $type === 'webhook' || $integration->base_url !== null;
    }
}

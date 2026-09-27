<?php

namespace App\Domains\Integrations\Connectors;

use App\Domains\Integrations\Enums\ConnectorCapability;
use App\Domains\Integrations\Models\Integration;

class RestApiConnector extends AbstractConnector
{
    public function key(): string
    {
        return 'rest-api';
    }

    public function label(): string
    {
        return 'REST API';
    }

    public function description(): string
    {
        return 'Outbound HTTP through the Integration Hub API client, including timeouts, retries, and rate limits.';
    }

    public function capabilities(): array
    {
        return [
            ConnectorCapability::Api->value,
            ConnectorCapability::QueueRetry->value,
        ];
    }

    public function matchesIntegration(?Integration $integration): bool
    {
        $type = $integration?->type?->value ?? (string) ($integration?->type ?? '');

        return in_array($type, ['rest_api', 'graphql'], true);
    }
}

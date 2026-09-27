<?php

namespace App\Domains\Integrations\Connectors;

use App\Domains\Integrations\Enums\ConnectorCapability;
use App\Domains\Integrations\Models\Integration;

class OAuthConnector extends AbstractConnector
{
    public function key(): string
    {
        return 'oauth2';
    }

    public function label(): string
    {
        return 'OAuth 2.0';
    }

    public function description(): string
    {
        return 'OAuth 2.0 access tokens and refresh through the API client. Refresh tokens are stored encrypted and never returned by the API.';
    }

    public function capabilities(): array
    {
        return [
            ConnectorCapability::OAuth->value,
            ConnectorCapability::Api->value,
            ConnectorCapability::Credentials->value,
        ];
    }

    public function priority(): int
    {
        return 60;
    }

    public function matchesIntegration(?Integration $integration): bool
    {
        $auth = $integration?->authentication_type?->value ?? (string) ($integration?->authentication_type ?? '');

        return $auth === 'oauth2';
    }
}

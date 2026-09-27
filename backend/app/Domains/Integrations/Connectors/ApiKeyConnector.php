<?php

namespace App\Domains\Integrations\Connectors;

use App\Domains\Integrations\Enums\ConnectorCapability;
use App\Domains\Integrations\Models\Integration;

class ApiKeyConnector extends AbstractConnector
{
    public function key(): string
    {
        return 'api-key';
    }

    public function label(): string
    {
        return 'API key';
    }

    public function description(): string
    {
        return 'API key authentication applied by the shared authentication manager. Secrets stay in the credential vault.';
    }

    public function capabilities(): array
    {
        return [
            ConnectorCapability::ApiKey->value,
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

        return $auth === 'api_key';
    }
}

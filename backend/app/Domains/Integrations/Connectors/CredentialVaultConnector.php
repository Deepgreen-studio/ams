<?php

namespace App\Domains\Integrations\Connectors;

use App\Domains\Integrations\Enums\ConnectorCapability;
use App\Domains\Integrations\Models\Integration;

class CredentialVaultConnector extends AbstractConnector
{
    public function key(): string
    {
        return 'credential-vault';
    }

    public function label(): string
    {
        return 'Credential vault';
    }

    public function description(): string
    {
        return 'Credentials are encrypted on the integration record. API responses expose key names only.';
    }

    public function capabilities(): array
    {
        return [
            ConnectorCapability::Credentials->value,
        ];
    }

    public function priority(): int
    {
        return 70;
    }

    public function matchesIntegration(?Integration $integration): bool
    {
        return $integration !== null;
    }
}

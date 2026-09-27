<?php

namespace App\Domains\Integrations\Connectors;

use App\Domains\Integrations\Enums\ConnectorCapability;
use App\Domains\Integrations\Models\Integration;

class MonitoringConnector extends AbstractConnector
{
    public function key(): string
    {
        return 'monitoring';
    }

    public function label(): string
    {
        return 'Monitoring';
    }

    public function description(): string
    {
        return 'Scheduled health checks use the connection engine and record health status on the integration.';
    }

    public function capabilities(): array
    {
        return [
            ConnectorCapability::Monitoring->value,
        ];
    }

    public function priority(): int
    {
        return 30;
    }

    public function matchesIntegration(?Integration $integration): bool
    {
        return $integration !== null && filled($integration->base_url);
    }
}

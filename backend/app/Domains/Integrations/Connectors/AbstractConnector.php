<?php

namespace App\Domains\Integrations\Connectors;

use App\Domains\Integrations\Contracts\IntegrationConnectorInterface;
use App\Domains\Integrations\Models\Integration;

abstract class AbstractConnector implements IntegrationConnectorInterface
{
    abstract public function key(): string;

    abstract public function label(): string;

    abstract public function description(): string;

    /**
     * @return list<string>
     */
    abstract public function capabilities(): array;

    public function priority(): int
    {
        return 40;
    }

    public function matchesIntegration(?Integration $integration): bool
    {
        return $integration !== null;
    }
}

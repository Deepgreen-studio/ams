<?php

namespace App\Domains\Integrations\Contracts;

use App\Domains\Integrations\Models\Integration;

interface IntegrationConnectorInterface
{
    public function key(): string;

    public function label(): string;

    public function description(): string;

    /**
     * @return list<string>
     */
    public function capabilities(): array;

    public function priority(): int;

    public function matchesIntegration(?Integration $integration): bool;
}

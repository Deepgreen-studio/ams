<?php

namespace App\Domains\Integrations\Connectors;

use App\Domains\Integrations\Enums\ConnectorCapability;
use App\Domains\Integrations\Models\Integration;

class ScheduledSyncConnector extends AbstractConnector
{
    public function key(): string
    {
        return 'scheduled-sync';
    }

    public function label(): string
    {
        return 'Scheduled sync';
    }

    public function description(): string
    {
        return 'Cron schedules enqueue sync runs on the sync queue. The connector framework delegates to the existing sync engine.';
    }

    public function capabilities(): array
    {
        return [
            ConnectorCapability::ScheduledSync->value,
            ConnectorCapability::QueueRetry->value,
        ];
    }

    public function priority(): int
    {
        return 30;
    }

    public function matchesIntegration(?Integration $integration): bool
    {
        return $integration !== null;
    }
}

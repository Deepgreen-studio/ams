<?php

namespace App\Domains\Integrations\Listeners;

use App\Domains\Integrations\Events\DataMappingCreated;
use App\Domains\Integrations\Events\DataMappingDeleted;
use App\Domains\Integrations\Events\DataMappingUpdated;
use App\Domains\Integrations\Events\IntegrationConfigurationUpdated;
use App\Domains\Integrations\Events\IntegrationConnectionExecuted;
use App\Domains\Integrations\Events\IntegrationCreated;
use App\Domains\Integrations\Events\IntegrationDeleted;
use App\Domains\Integrations\Events\IntegrationRestored;
use App\Domains\Integrations\Events\IntegrationUpdated;
use App\Domains\Integrations\Events\SyncRunCompleted;
use App\Domains\Integrations\Events\SyncRunFailed;
use App\Domains\Integrations\Events\SyncRunStarted;
use App\Domains\Integrations\Events\WebhookCreated;
use App\Domains\Integrations\Events\WebhookDeleted;
use App\Domains\Integrations\Events\WebhookDelivered;
use App\Domains\Integrations\Events\WebhookFailed;
use App\Domains\Integrations\Events\WebhookUpdated;

/** Placeholder for future integration notification workflows. */
class PrepareIntegrationNotifications
{
    public function handleIntegrationCreated(IntegrationCreated $event): void {}

    public function handleIntegrationUpdated(IntegrationUpdated $event): void {}

    public function handleIntegrationDeleted(IntegrationDeleted $event): void {}

    public function handleIntegrationRestored(IntegrationRestored $event): void {}

    public function handleConfigurationUpdated(IntegrationConfigurationUpdated $event): void {}

    public function handleConnectionExecuted(IntegrationConnectionExecuted $event): void {}

    public function handleWebhookCreated(WebhookCreated $event): void {}

    public function handleWebhookUpdated(WebhookUpdated $event): void {}

    public function handleWebhookDeleted(WebhookDeleted $event): void {}

    public function handleWebhookDelivered(WebhookDelivered $event): void {}

    public function handleWebhookFailed(WebhookFailed $event): void {}

    public function handleSyncRunStarted(SyncRunStarted $event): void {}

    public function handleSyncRunCompleted(SyncRunCompleted $event): void {}

    public function handleSyncRunFailed(SyncRunFailed $event): void {}

    public function handleDataMappingCreated(DataMappingCreated $event): void {}

    public function handleDataMappingUpdated(DataMappingUpdated $event): void {}

    public function handleDataMappingDeleted(DataMappingDeleted $event): void {}
}

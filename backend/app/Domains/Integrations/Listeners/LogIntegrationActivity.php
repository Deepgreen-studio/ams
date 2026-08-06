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

class LogIntegrationActivity
{
    public function handleIntegrationCreated(IntegrationCreated $event): void
    {
        activity('integrations')
            ->causedBy($event->actor)
            ->performedOn($event->integration)
            ->withProperties([
                'event' => 'integration_created',
                'name' => $event->integration->name,
                'type' => $event->integration->type?->value ?? $event->integration->type,
            ])
            ->log('Integration created');
    }

    public function handleIntegrationUpdated(IntegrationUpdated $event): void
    {
        activity('integrations')
            ->causedBy($event->actor)
            ->performedOn($event->integration)
            ->withProperties([
                'event' => 'integration_updated',
                'name' => $event->integration->name,
                'status' => $event->integration->status?->value ?? $event->integration->status,
            ])
            ->log('Integration updated');
    }

    public function handleIntegrationDeleted(IntegrationDeleted $event): void
    {
        activity('integrations')
            ->causedBy($event->actor)
            ->performedOn($event->integration)
            ->withProperties([
                'event' => 'integration_deleted',
                'name' => $event->integration->name,
            ])
            ->log('Integration deleted');
    }

    public function handleIntegrationRestored(IntegrationRestored $event): void
    {
        activity('integrations')
            ->causedBy($event->actor)
            ->performedOn($event->integration)
            ->withProperties([
                'event' => 'integration_restored',
                'name' => $event->integration->name,
            ])
            ->log('Integration restored');
    }

    public function handleConfigurationUpdated(IntegrationConfigurationUpdated $event): void
    {
        activity('integrations')
            ->causedBy($event->actor)
            ->performedOn($event->integration)
            ->withProperties([
                'event' => 'integration_configuration_updated',
                'name' => $event->integration->name,
            ])
            ->log('Integration configuration updated');
    }

    public function handleConnectionExecuted(IntegrationConnectionExecuted $event): void
    {
        activity('integrations')
            ->causedBy($event->actor)
            ->performedOn($event->integration)
            ->withProperties([
                'event' => 'integration_connection_executed',
                'request_type' => $event->log->request_type?->value ?? $event->log->request_type,
                'method' => $event->log->method,
                'success' => $event->log->success,
                'status' => $event->log->response_status,
            ])
            ->log('Integration connection executed');
    }

    public function handleWebhookCreated(WebhookCreated $event): void
    {
        activity('webhooks')
            ->causedBy($event->actor)
            ->performedOn($event->webhook)
            ->withProperties(['event' => 'webhook_created', 'name' => $event->webhook->name])
            ->log('Webhook created');
    }

    public function handleWebhookUpdated(WebhookUpdated $event): void
    {
        activity('webhooks')
            ->causedBy($event->actor)
            ->performedOn($event->webhook)
            ->withProperties(['event' => 'webhook_updated', 'name' => $event->webhook->name])
            ->log('Webhook updated');
    }

    public function handleWebhookDeleted(WebhookDeleted $event): void
    {
        activity('webhooks')
            ->causedBy($event->actor)
            ->performedOn($event->webhook)
            ->withProperties(['event' => 'webhook_deleted', 'name' => $event->webhook->name])
            ->log('Webhook deleted');
    }

    public function handleWebhookDelivered(WebhookDelivered $event): void
    {
        activity('webhooks')
            ->performedOn($event->webhook)
            ->withProperties([
                'event' => 'webhook_delivered',
                'log_uuid' => $event->log->uuid,
                'status' => $event->log->response_status,
            ])
            ->log('Webhook delivered');
    }

    public function handleWebhookFailed(WebhookFailed $event): void
    {
        activity('webhooks')
            ->performedOn($event->webhook)
            ->withProperties([
                'event' => 'webhook_failed',
                'log_uuid' => $event->log->uuid,
                'error' => $event->log->error_message,
            ])
            ->log('Webhook delivery failed');
    }

    public function handleSyncRunStarted(SyncRunStarted $event): void
    {
        $logger = activity('syncs')->performedOn($event->config)->withProperties([
            'event' => 'sync_run_started',
            'run_uuid' => $event->run->uuid,
            'trigger' => $event->run->trigger?->value ?? $event->run->trigger,
        ]);
        if ($event->actor) {
            $logger->causedBy($event->actor);
        }
        $logger->log('Sync run started');
    }

    public function handleSyncRunCompleted(SyncRunCompleted $event): void
    {
        activity('syncs')
            ->performedOn($event->config)
            ->withProperties([
                'event' => 'sync_run_completed',
                'run_uuid' => $event->run->uuid,
                'imported' => $event->run->imported,
                'updated' => $event->run->updated,
                'failed' => $event->run->failed,
            ])
            ->log('Sync run completed');
    }

    public function handleSyncRunFailed(SyncRunFailed $event): void
    {
        activity('syncs')
            ->performedOn($event->config)
            ->withProperties([
                'event' => 'sync_run_failed',
                'run_uuid' => $event->run->uuid,
                'error' => $event->run->error_message,
            ])
            ->log('Sync run failed');
    }

    public function handleDataMappingCreated(DataMappingCreated $event): void
    {
        activity('mappings')
            ->causedBy($event->actor)
            ->performedOn($event->mapping)
            ->withProperties([
                'event' => 'data_mapping_created',
                'name' => $event->mapping->name,
                'source_entity' => $event->mapping->source_entity,
            ])
            ->log('Data mapping created');
    }

    public function handleDataMappingUpdated(DataMappingUpdated $event): void
    {
        activity('mappings')
            ->causedBy($event->actor)
            ->performedOn($event->mapping)
            ->withProperties([
                'event' => 'data_mapping_updated',
                'name' => $event->mapping->name,
                'version' => $event->mapping->version,
            ])
            ->log('Data mapping updated');
    }

    public function handleDataMappingDeleted(DataMappingDeleted $event): void
    {
        activity('mappings')
            ->causedBy($event->actor)
            ->performedOn($event->mapping)
            ->withProperties([
                'event' => 'data_mapping_deleted',
                'name' => $event->mapping->name,
            ])
            ->log('Data mapping deleted');
    }
}

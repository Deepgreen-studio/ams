<?php

namespace App\Domains\Integrations\Providers;

use App\Domains\Integrations\Connectors\ApiKeyConnector;
use App\Domains\Integrations\Connectors\CredentialVaultConnector;
use App\Domains\Integrations\Connectors\MonitoringConnector;
use App\Domains\Integrations\Connectors\OAuthConnector;
use App\Domains\Integrations\Connectors\RestApiConnector;
use App\Domains\Integrations\Connectors\ScheduledSyncConnector;
use App\Domains\Integrations\Connectors\WebhookEngineConnector;
use App\Domains\Integrations\Handlers\EasyCareIncomingWebhookHandler;
use App\Domains\Integrations\Handlers\GenericSupportIncomingWebhookHandler;
use App\Domains\Integrations\Services\ConnectorRegistry;
use Illuminate\Support\ServiceProvider;

class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([
            CredentialVaultConnector::class,
            OAuthConnector::class,
            ApiKeyConnector::class,
            RestApiConnector::class,
            WebhookEngineConnector::class,
            ScheduledSyncConnector::class,
            MonitoringConnector::class,
            EasyCareIncomingWebhookHandler::class,
            GenericSupportIncomingWebhookHandler::class,
        ], 'integration.connectors');

        $this->app->singleton(ConnectorRegistry::class, function ($app): ConnectorRegistry {
            return new ConnectorRegistry($app->tagged('integration.connectors'));
        });
    }
}

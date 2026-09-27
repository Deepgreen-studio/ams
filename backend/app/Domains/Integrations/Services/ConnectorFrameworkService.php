<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Integrations\Enums\IntegrationType;
use App\Domains\Integrations\Jobs\RunIntegrationHealthCheckJob;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Integrations\Repositories\IntegrationRepository;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConnectorFrameworkService
{
    public function __construct(
        private readonly ConnectorRegistry $connectorRegistry,
        private readonly CredentialVault $credentialVault,
        private readonly OAuthTokenService $oauthTokenService,
        private readonly IntegrationRepository $integrationRepository,
        private readonly IntegrationSyncService $syncService,
        private readonly WebhookDeliveryService $webhookDeliveryService,
        private readonly IntegrationConnectionService $connectionService,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function catalog(): array
    {
        return $this->connectorRegistry->catalog();
    }

    /**
     * @return array<string, mixed>
     */
    public function describe(Integration $integration): array
    {
        $matched = $this->connectorRegistry->forIntegration($integration);
        $capabilities = [];
        foreach ($matched as $connector) {
            foreach ($connector->capabilities() as $capability) {
                $capabilities[$capability] = $capability;
            }
        }

        return [
            'integration_uuid' => $integration->uuid,
            'connectors' => array_map(
                fn ($connector): array => $this->connectorRegistry->definition($connector),
                $matched,
            ),
            'capabilities' => array_values($capabilities),
            'credentials' => $this->credentialVault->profile($integration),
        ];
    }

    /**
     * @param array{oauth?: bool, retries?: bool, sync?: bool, health?: bool} $options
     * @return array{oauth_refreshed: int, webhook_retries: int, sync_runs: int, health_checks: int}
     */
    public function maintain(array $options = []): array
    {
        $oauth = $options['oauth'] ?? true;
        $retries = $options['retries'] ?? true;
        $sync = $options['sync'] ?? false;
        $health = $options['health'] ?? true;

        return [
            'oauth_refreshed' => $oauth ? $this->refreshDueOauthTokens() : 0,
            'webhook_retries' => $retries ? $this->webhookDeliveryService->dispatchDueRetries() : 0,
            'sync_runs' => $sync ? $this->syncService->dispatchDueSchedules() : 0,
            'health_checks' => $health ? $this->queueHealthChecks() : 0,
        ];
    }

    public function refreshDueOauthTokens(): int
    {
        $refreshed = 0;

        foreach ($this->integrationRepository->activeOAuth() as $integration) {
            if (! $this->oauthTokenService->isDue($integration)) {
                continue;
            }

            try {
                $this->oauthTokenService->refresh($integration, $this->actorFor($integration));
                $refreshed++;
            } catch (Throwable $exception) {
                Log::warning('OAuth token refresh failed.', [
                    'integration_id' => $integration->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $refreshed;
    }

    public function queueHealthChecks(): int
    {
        $queued = 0;
        $interval = (int) config('integration_connectors.health_check_interval_minutes', 15);

        foreach ($this->integrationRepository->dueForHealthCheck($interval) as $integration) {
            RunIntegrationHealthCheckJob::dispatch((int) $integration->id);
            $queued++;
        }

        return $queued;
    }

    public function checkHealth(int $integrationId): void
    {
        $integration = $this->integrationRepository->find($integrationId);
        if (! $integration instanceof Integration) {
            return;
        }

        $type = $integration->type instanceof IntegrationType
            ? $integration->type->value
            : (string) $integration->type;

        if (! in_array($type, ['rest_api', 'graphql', 'webhook'], true) || blank($integration->base_url)) {
            return;
        }

        $actor = $this->actorFor($integration);
        if ($actor === null) {
            Log::warning('Integration health check skipped; no system actor is available.', [
                'integration_id' => $integration->id,
            ]);

            return;
        }

        try {
            $this->connectionService->testConnection($integration->uuid, $actor);
        } catch (Throwable $exception) {
            Log::warning('Integration health check failed.', [
                'integration_id' => $integration->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function actorFor(Integration $integration): ?User
    {
        if ($integration->created_by) {
            $creator = User::query()->find($integration->created_by);
            if ($creator instanceof User) {
                return $creator;
            }
        }

        $admin = User::query()->where('email', 'admin@ams.test')->first();
        if ($admin instanceof User) {
            return $admin;
        }

        $fallback = User::query()->orderBy('id')->first();

        return $fallback instanceof User ? $fallback : null;
    }
}

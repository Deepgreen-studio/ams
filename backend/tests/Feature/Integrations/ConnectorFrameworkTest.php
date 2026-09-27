<?php

namespace Tests\Feature\Integrations;

use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Jobs\DeliverOutgoingWebhookJob;
use App\Domains\Integrations\Jobs\RunIntegrationHealthCheckJob;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Integrations\Models\SyncConfig;
use App\Domains\Integrations\Models\Webhook;
use App\Domains\Integrations\Models\WebhookLog;
use App\Domains\Integrations\Services\ConnectorFrameworkService;
use App\Domains\Integrations\Services\ConnectorRegistry;
use App\Domains\Queue\Jobs\ProcessImportJob;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConnectorFrameworkTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'connector-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Connector Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_catalog_lists_framework_and_product_connectors(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/integrations/connectors')
            ->assertOk()
            ->assertJsonPath('success', true);

        $keys = collect($response->json('data.connectors'))->pluck('key')->all();

        $this->assertContains('rest-api', $keys);
        $this->assertContains('api-key', $keys);
        $this->assertContains('oauth2', $keys);
        $this->assertContains('webhook-engine', $keys);
        $this->assertContains('scheduled-sync', $keys);
        $this->assertContains('monitoring', $keys);
        $this->assertContains('credential-vault', $keys);
        $this->assertContains('easycare', $keys);
        $this->assertContains('generic-support', $keys);
    }

    public function test_webhook_handlers_run_product_connectors_before_generic_support(): void
    {
        $handlers = app(ConnectorRegistry::class)->webhookHandlers();

        $this->assertSame('easycare', $handlers[0]->key());
        $this->assertSame('generic-support', $handlers[1]->key());
    }

    public function test_profile_matches_connectors_and_hides_credential_values(): void
    {
        Sanctum::actingAs($this->admin);
        $integration = $this->makeIntegration([
            'slug' => 'billing-api',
            'authentication_type' => 'api_key',
            'credentials' => [
                'api_key' => 'super-secret-key',
                'api_key_header' => 'X-API-Key',
            ],
        ]);

        $response = $this->getJson('/api/v1/integrations/' . $integration->uuid . '/connector')
            ->assertOk();

        $keys = collect($response->json('data.connector.connectors'))->pluck('key')->all();
        $this->assertContains('api-key', $keys);
        $this->assertContains('credential-vault', $keys);
        $this->assertNotContains('easycare', $keys);
        $this->assertSame(['api_key', 'api_key_header'], $response->json('data.connector.credentials.keys'));
        $response->assertDontSee('super-secret-key');
    }

    public function test_credentials_are_merged_without_returning_secrets(): void
    {
        Sanctum::actingAs($this->admin);
        $integration = $this->makeIntegration([
            'authentication_type' => 'api_key',
            'credentials' => ['api_key' => 'old-key'],
        ]);

        $response = $this->putJson('/api/v1/integrations/' . $integration->uuid . '/connector/credentials', [
            'credentials' => [
                'api_key_header' => 'X-Api-Key',
                'api_key' => '',
            ],
        ])->assertOk();

        $response->assertDontSee('old-key');
        $integration->refresh();
        $this->assertSame('old-key', $integration->credentials['api_key']);
        $this->assertSame('X-Api-Key', $integration->credentials['api_key_header']);
    }

    public function test_oauth_refresh_uses_the_api_client_and_stores_the_token(): void
    {
        Sanctum::actingAs($this->admin);
        Http::fake([
            'auth.example.test/*' => Http::response([
                'access_token' => 'new-access-token',
                'token_type' => 'Bearer',
                'expires_in' => 1200,
            ], 200),
        ]);

        $integration = $this->makeIntegration([
            'authentication_type' => 'oauth2',
            'credentials' => [
                'token_url' => 'https://auth.example.test/oauth/token',
                'client_id' => 'client-id',
                'client_secret' => 'client-secret',
                'oauth_refresh_token' => 'refresh-token',
                'oauth_expires_at' => now()->subHour()->toIso8601String(),
            ],
        ]);

        $response = $this->postJson('/api/v1/integrations/' . $integration->uuid . '/connector/oauth/refresh')
            ->assertOk()
            ->assertJsonPath('data.connector.credentials.refresh_available', true);

        $response->assertDontSee('new-access-token');
        $response->assertDontSee('client-secret');
        $response->assertDontSee('refresh-token');

        $integration->refresh();
        $this->assertSame('new-access-token', $integration->credentials['oauth_access_token']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://auth.example.test/oauth/token'
                && str_contains((string) $request->body(), 'grant_type=refresh_token')
                && str_contains((string) $request->body(), 'client_secret=client-secret');
        });
    }

    public function test_viewer_cannot_replace_credentials(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('integrations.view');
        Sanctum::actingAs($user);

        $integration = $this->makeIntegration();

        $this->putJson('/api/v1/integrations/' . $integration->uuid . '/connector/credentials', [
            'credentials' => ['api_key' => 'nope'],
        ])->assertForbidden();
    }

    public function test_maintenance_refreshes_oauth_and_queues_retries_and_health(): void
    {
        Queue::fake();
        Http::fake([
            'auth.example.test/*' => Http::response([
                'access_token' => 'scheduled-access',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $integration = $this->makeIntegration([
            'authentication_type' => 'oauth2',
            'base_url' => 'https://api.example.test',
            'health_check_path' => '/health',
            'created_by' => $this->admin->id,
            'credentials' => [
                'token_url' => 'https://auth.example.test/oauth/token',
                'client_id' => 'client-id',
                'client_secret' => 'client-secret',
                'oauth_refresh_token' => 'refresh-token',
            ],
        ]);

        $webhook = Webhook::query()->create([
            'company_id' => $this->company->id,
            'integration_id' => $integration->id,
            'name' => 'Outbound',
            'slug' => 'outbound',
            'direction' => 'outgoing',
            'status' => 'active',
            'url' => 'https://hooks.example.test/in',
        ]);

        $log = WebhookLog::query()->create([
            'webhook_id' => $webhook->id,
            'company_id' => $this->company->id,
            'direction' => 'outgoing',
            'status' => 'retrying',
            'attempts' => 1,
            'max_attempts' => 3,
            'next_retry_at' => now()->subMinute(),
            'request_body' => '{}',
        ]);

        $this->artisan('integrations:maintain')->assertSuccessful();

        $integration->refresh();
        $log->refresh();
        $this->assertSame('scheduled-access', $integration->credentials['oauth_access_token']);
        $this->assertSame('pending', $log->status instanceof \BackedEnum ? $log->status->value : $log->status);
        $this->assertNull($log->next_retry_at);
        Queue::assertPushed(DeliverOutgoingWebhookJob::class);
        Queue::assertPushed(RunIntegrationHealthCheckJob::class);
    }

    public function test_health_check_uses_the_connection_engine(): void
    {
        Http::fake([
            'api.example.test/*' => Http::response(['ok' => true], 200),
        ]);

        $integration = $this->makeIntegration([
            'base_url' => 'https://api.example.test',
            'health_check_path' => '/health',
            'created_by' => $this->admin->id,
            'credentials' => ['bearer_token' => 'secret-token'],
        ]);

        app(ConnectorFrameworkService::class)->checkHealth($integration->id);

        $integration->refresh();
        $this->assertSame('healthy', $integration->health_status?->value ?? $integration->health_status);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.example.test/health');
    }

    public function test_scheduled_sync_can_be_delegated_by_the_framework(): void
    {
        Queue::fake();
        $integration = $this->makeIntegration();

        SyncConfig::query()->create([
            'company_id' => $this->company->id,
            'integration_id' => $integration->id,
            'name' => 'Hourly import',
            'slug' => 'hourly-import',
            'direction' => 'import',
            'default_mode' => 'incremental',
            'trigger_type' => 'scheduled',
            'schedule_cron' => '* * * * *',
            'is_enabled' => true,
        ]);

        $result = app(ConnectorFrameworkService::class)->maintain([
            'oauth' => false,
            'retries' => false,
            'sync' => true,
            'health' => false,
        ]);

        $this->assertSame(1, $result['sync_runs']);
        Queue::assertPushed(ProcessImportJob::class);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function makeIntegration(array $overrides = []): Integration
    {
        return Integration::query()->create(array_merge([
            'company_id' => $this->company->id,
            'name' => 'External API',
            'slug' => 'external-api',
            'type' => 'rest_api',
            'status' => 'active',
            'authentication_type' => 'bearer_token',
            'base_url' => 'https://api.example.test',
            'health_check_path' => '/health',
            'timeout' => 15,
            'retry_attempts' => 1,
            'health_status' => 'unknown',
            'created_by' => $this->admin->id,
        ], $overrides));
    }
}

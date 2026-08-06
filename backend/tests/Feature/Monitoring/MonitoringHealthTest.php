<?php

namespace Tests\Feature\Monitoring;

use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Integrations\Models\IntegrationConnectionLog;
use App\Domains\Integrations\Models\Webhook;
use App\Domains\Integrations\Models\WebhookLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MonitoringHealthTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    private Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'monitor-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Monitor Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->integration = Integration::query()->create([
            'company_id' => $this->company->id,
            'name' => 'CRM API',
            'slug' => 'crm-api-monitor',
            'type' => 'rest_api',
            'status' => 'active',
            'authentication_type' => 'bearer_token',
            'base_url' => 'https://api.monitor.example',
            'timeout' => 30,
            'retry_attempts' => 1,
            'credentials' => ['bearer_token' => 'token'],
            'health_status' => 'healthy',
        ]);
    }

    public function test_dashboard_returns_health_and_performance_scores(): void
    {
        Sanctum::actingAs($this->admin);
        $this->seedLogs();

        $this->getJson('/api/v1/monitoring/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'scores' => ['health_score', 'performance_score', 'uptime_percent', 'downtime_percent', 'error_rate'],
                    'statuses',
                    'api',
                    'webhooks',
                    'queue',
                    'charts' => ['response_history', 'health_trend'],
                ],
            ]);
    }

    public function test_api_and_webhook_monitors_and_history(): void
    {
        Sanctum::actingAs($this->admin);
        $this->seedLogs();

        $this->getJson('/api/v1/monitoring/api')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 4);

        $this->getJson('/api/v1/monitoring/webhooks')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 2);

        $this->getJson('/api/v1/monitoring/response-history?hours=24')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_alert_crud_and_capture_can_trigger_alert(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/monitoring/alerts', [
            'name' => 'High error rate',
            'metric' => 'error_rate',
            'operator' => 'gte',
            'threshold' => 1,
            'cooldown_minutes' => 1,
            'channels' => ['in_app'],
        ])->assertCreated();

        $uuid = $create->json('data.alert.uuid');

        IntegrationConnectionLog::query()->create([
            'integration_id' => $this->integration->id,
            'company_id' => $this->company->id,
            'request_type' => 'request',
            'method' => 'GET',
            'url' => 'https://api.monitor.example/fail',
            'response_status' => 500,
            'duration_ms' => 1200,
            'attempts' => 1,
            'success' => false,
            'error_message' => 'Server error',
        ]);

        $this->postJson('/api/v1/monitoring/capture')
            ->assertOk()
            ->assertJsonPath('data.events_triggered', 1);

        $this->assertDatabaseHas('monitoring_snapshots', ['scope' => 'hub']);
        $this->assertDatabaseCount('monitoring_alert_events', 1);

        $this->getJson('/api/v1/monitoring/alerts')
            ->assertOk()
            ->assertJsonPath('data.alerts.meta.total', 1);

        $this->putJson('/api/v1/monitoring/alerts/'.$uuid, [
            'is_enabled' => false,
        ])->assertOk()->assertJsonPath('data.alert.is_enabled', false);
    }

    public function test_queue_health_endpoint(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/monitoring/queue')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['pending', 'failed', 'running', 'health_score', 'status'],
            ]);
    }

    private function seedLogs(): void
    {
        foreach ([
            ['success' => true, 'duration_ms' => 120, 'status' => 200],
            ['success' => true, 'duration_ms' => 180, 'status' => 200],
            ['success' => false, 'duration_ms' => 900, 'status' => 500],
        ] as $row) {
            IntegrationConnectionLog::query()->create([
                'integration_id' => $this->integration->id,
                'company_id' => $this->company->id,
                'request_type' => 'request',
                'method' => 'GET',
                'url' => 'https://api.monitor.example/items',
                'response_status' => $row['status'],
                'duration_ms' => $row['duration_ms'],
                'attempts' => 1,
                'success' => $row['success'],
            ]);
        }

        IntegrationConnectionLog::query()->create([
            'integration_id' => $this->integration->id,
            'company_id' => $this->company->id,
            'request_type' => 'authentication_test',
            'method' => 'GET',
            'url' => 'https://api.monitor.example/me',
            'response_status' => 200,
            'duration_ms' => 90,
            'attempts' => 1,
            'success' => true,
        ]);

        $webhook = Webhook::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Monitor Hook',
            'slug' => 'monitor-hook',
            'direction' => 'outgoing',
            'status' => 'active',
            'url' => 'https://hooks.example/monitor',
            'secret' => 'secret',
            'signature_algorithm' => 'hmac_sha256',
            'signature_header' => 'X-AMS-Signature',
            'timeout' => 10,
            'retry_attempts' => 1,
            'retry_delay_seconds' => 30,
        ]);

        WebhookLog::query()->create([
            'webhook_id' => $webhook->id,
            'company_id' => $this->company->id,
            'direction' => 'outgoing',
            'event_name' => 'webhook.test',
            'status' => 'success',
            'method' => 'POST',
            'url' => $webhook->url,
            'duration_ms' => 100,
            'attempts' => 1,
            'max_attempts' => 3,
            'is_test' => true,
        ]);

        WebhookLog::query()->create([
            'webhook_id' => $webhook->id,
            'company_id' => $this->company->id,
            'direction' => 'outgoing',
            'event_name' => 'webhook.test',
            'status' => 'failed',
            'method' => 'POST',
            'url' => $webhook->url,
            'duration_ms' => 300,
            'attempts' => 2,
            'max_attempts' => 3,
            'error_message' => 'timeout',
            'is_test' => false,
        ]);
    }
}

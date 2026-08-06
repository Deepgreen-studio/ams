<?php

namespace Tests\Feature\Applications;

use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApplicationMonitoringManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Application $application;

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

        $company = Company::query()->create([
            'company_name' => 'Monitor Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->application = Application::query()->create([
            'company_id' => $company->id,
            'name' => 'Monitor App',
            'slug' => 'monitor-app',
            'platform' => 'android',
            'category' => 'business',
            'status' => 'active',
            'visibility' => 'private',
        ]);
    }

    public function test_can_ingest_crash_and_view_dashboard(): void
    {
        Sanctum::actingAs($this->admin);

        $ingest = $this->postJson('/api/v1/applications/'.$this->application->uuid.'/monitoring/ingest/crash', [
            'type' => 'crash',
            'title' => 'NullPointerException',
            'stack_trace' => "at CheckoutActivity.onCreate\nat Activity.performCreate",
            'crash_log' => 'FATAL EXCEPTION',
            'version_label' => '1.2.0',
            'device_model' => 'Pixel 8',
            'device_os' => 'Android',
            'device_os_version' => '14',
            'device_id' => 'device-1',
            'memory_usage_mb' => 312.5,
            'battery_level' => 55,
        ]);

        $ingest->assertCreated()
            ->assertJsonPath('data.crash.type', 'crash')
            ->assertJsonPath('data.crash.version_label', '1.2.0')
            ->assertJsonPath('data.crash.device_model', 'Pixel 8');

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/monitoring/ingest/crash', [
            'type' => 'anr',
            'title' => 'Input dispatching timed out',
            'stack_trace' => 'ANR in com.example',
            'device_model' => 'Galaxy S24',
            'device_os' => 'Android',
        ])->assertCreated();

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/monitoring/crash-dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 2)
            ->assertJsonPath('data.summary.crash', 1)
            ->assertJsonPath('data.summary.anr', 1);

        $uuid = $ingest->json('data.crash.uuid');
        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/monitoring/crashes/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.crash.stack_trace', "at CheckoutActivity.onCreate\nat Activity.performCreate");
    }

    public function test_deduplicates_by_fingerprint(): void
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'type' => 'crash',
            'title' => 'Same Crash',
            'stack_trace' => 'same stack',
            'fingerprint' => 'fp-shared-1',
        ];

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/monitoring/ingest/crash', $payload)
            ->assertCreated()
            ->assertJsonPath('data.crash.occurrence_count', 1);

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/monitoring/ingest/crash', $payload)
            ->assertCreated()
            ->assertJsonPath('data.crash.occurrence_count', 2);

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/monitoring/crash-dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 1);
    }

    public function test_health_ingest_and_alert_trigger(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/monitoring/alerts', [
            'name' => 'Low health',
            'metric' => 'health_score',
            'operator' => 'lte',
            'threshold' => 80,
            'severity' => 'critical',
            'cooldown_minutes' => 1,
        ])->assertCreated();

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/monitoring/ingest/health', [
            'health_score' => 55,
            'crash_rate' => 20,
            'anr_rate' => 5,
            'api_error_rate' => 10,
            'avg_response_time_ms' => 900,
            'avg_memory_usage_mb' => 400,
            'avg_battery_usage' => 12,
            'sample_size' => 100,
        ])
            ->assertCreated()
            ->assertJsonPath('data.metric.health_score', 55);

        $alerts = $this->getJson('/api/v1/applications/'.$this->application->uuid.'/monitoring/alerts')
            ->assertOk();

        $this->assertNotEmpty($alerts->json('data.events'));
        $this->assertSame('open', $alerts->json('data.events.0.status'));
    }

    public function test_device_statistics_and_api_error_ingest(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/monitoring/ingest/api-error', [
            'endpoint' => '/api/v1/checkout',
            'http_status' => 500,
            'response_time_ms' => 1200,
            'device_model' => 'Pixel 8',
            'device_os' => 'Android',
            'device_os_version' => '14',
        ])->assertCreated()
            ->assertJsonPath('data.crash.type', 'api_error');

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/monitoring/device-statistics')
            ->assertOk()
            ->assertJsonPath('data.devices.0.device_model', 'Pixel 8');

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/monitoring/health-dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => ['latest' => ['health_score'], 'chart']]);
    }
}

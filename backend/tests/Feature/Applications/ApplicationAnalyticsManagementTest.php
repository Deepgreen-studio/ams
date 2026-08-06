<?php

namespace Tests\Feature\Applications;

use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApplicationAnalyticsManagementTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'analytics-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $company = Company::query()->create([
            'company_name' => 'Analytics Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->application = Application::query()->create([
            'company_id' => $company->id,
            'name' => 'Analytics App',
            'slug' => 'analytics-app',
            'platform' => 'android',
            'category' => 'business',
            'status' => 'active',
            'visibility' => 'private',
        ]);
    }

    public function test_can_ingest_analytics_and_load_dashboard(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/analytics/ingest', [
            'metric_date' => now()->toDateString(),
            'active_users' => 1200,
            'daily_users' => 800,
            'monthly_users' => 5400,
            'avg_session_seconds' => 320,
            'retention_d1' => 42.5,
            'retention_d7' => 28.1,
            'retention_d30' => 15.4,
            'installs' => 90,
            'uninstalls' => 12,
            'sessions' => 2100,
            'countries' => [
                ['country_code' => 'US', 'country_name' => 'United States', 'users' => 400, 'sessions' => 900, 'installs' => 30],
                ['country_code' => 'BD', 'country_name' => 'Bangladesh', 'users' => 250, 'sessions' => 600, 'installs' => 40],
            ],
            'devices' => [
                ['device_model' => 'Pixel 8', 'os_name' => 'Android', 'os_version' => '14', 'users' => 300, 'sessions' => 700],
                ['device_model' => 'iPhone 15', 'os_name' => 'iOS', 'os_version' => '17', 'users' => 220, 'sessions' => 500],
            ],
            'heatmap' => [
                ['day_of_week' => 1, 'hour' => 9, 'activity_count' => 120],
                ['day_of_week' => 1, 'hour' => 10, 'activity_count' => 180],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.daily.daily_users', 800)
            ->assertJsonPath('data.daily.monthly_users', 5400);

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/analytics/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.daily_users', 800)
            ->assertJsonPath('data.summary.installs', 90)
            ->assertJsonPath('data.top_countries.0.country_code', 'US');

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/analytics/countries')
            ->assertOk()
            ->assertJsonPath('data.countries.1.country_code', 'BD');

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/analytics/devices')
            ->assertOk()
            ->assertJsonPath('data.devices.0.device_model', 'Pixel 8')
            ->assertJsonPath('data.os_versions.0.os_name', 'Android');

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/analytics/heatmap')
            ->assertOk()
            ->assertJsonPath('data.matrix.1.9', 120)
            ->assertJsonPath('data.max', 180);
    }

    public function test_trends_endpoint_returns_metric_series(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/analytics/ingest', [
            'metric_date' => now()->subDay()->toDateString(),
            'daily_users' => 500,
            'installs' => 40,
        ])->assertCreated();

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/analytics/ingest', [
            'metric_date' => now()->toDateString(),
            'daily_users' => 700,
            'installs' => 55,
        ])->assertCreated();

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/analytics/trends?metric=daily_users')
            ->assertOk()
            ->assertJsonPath('data.metric', 'daily_users')
            ->assertJsonPath('data.values.1', 700);
    }

    public function test_upsert_updates_same_day_metrics(): void
    {
        Sanctum::actingAs($this->admin);

        $date = now()->toDateString();

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/analytics/ingest', [
            'metric_date' => $date,
            'daily_users' => 100,
            'installs' => 10,
        ])->assertCreated();

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/analytics/ingest', [
            'metric_date' => $date,
            'daily_users' => 150,
            'installs' => 18,
        ])->assertCreated()
            ->assertJsonPath('data.daily.daily_users', 150);

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/analytics/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.daily_users', 150)
            ->assertJsonPath('data.summary.installs', 18);
    }
}

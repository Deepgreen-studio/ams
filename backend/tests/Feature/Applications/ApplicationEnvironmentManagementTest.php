<?php

namespace Tests\Feature\Applications;

use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationEnvironment;
use App\Domains\Companies\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApplicationEnvironmentManagementTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'env-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $company = Company::query()->create([
            'company_name' => 'Env Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->application = Application::query()->create([
            'company_id' => $company->id,
            'name' => 'Commerce App',
            'slug' => 'commerce-app',
            'platform' => 'web',
            'category' => 'business',
            'status' => 'active',
            'visibility' => 'private',
        ]);
    }

    public function test_admin_can_create_environment_with_masked_variables(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/applications/'.$this->application->uuid.'/environments', [
            'name' => 'Staging',
            'type' => 'staging',
            'api_url' => 'https://api.staging.example.com',
            'web_url' => 'https://staging.example.com',
            'status' => 'active',
            'variables' => [
                ['key' => 'API_KEY', 'value' => 'super-secret'],
                ['key' => 'APP_DEBUG', 'value' => 'false'],
            ],
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.environment.type', 'staging')
            ->assertJsonPath('data.environment.has_variables', true)
            ->assertJsonPath('data.environment.variables.0.key', 'API_KEY')
            ->assertJsonPath('data.environment.variables.0.masked_value', '********');

        $this->assertStringNotContainsString('super-secret', $create->getContent());

        $this->assertDatabaseHas('application_environments', [
            'application_id' => $this->application->id,
            'type' => 'staging',
        ]);
    }

    public function test_dashboard_and_switch_environment(): void
    {
        Sanctum::actingAs($this->admin);

        $dev = ApplicationEnvironment::query()->create([
            'application_id' => $this->application->id,
            'name' => 'Development',
            'slug' => 'development',
            'type' => 'development',
            'api_url' => 'https://api.dev.example.com',
            'status' => 'active',
            'health_status' => 'unknown',
            'is_current' => true,
        ]);

        $prod = ApplicationEnvironment::query()->create([
            'application_id' => $this->application->id,
            'name' => 'Production',
            'slug' => 'production',
            'type' => 'production',
            'api_url' => 'https://api.example.com',
            'status' => 'active',
            'health_status' => 'healthy',
            'is_current' => false,
        ]);

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/environments/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 2)
            ->assertJsonPath('data.current_environment.uuid', $dev->uuid);

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/environments/'.$prod->uuid.'/switch')
            ->assertOk()
            ->assertJsonPath('data.environment.uuid', $prod->uuid)
            ->assertJsonPath('data.environment.is_current', true);

        $dev->refresh();
        $prod->refresh();
        $this->assertFalse($dev->is_current);
        $this->assertTrue($prod->is_current);
    }

    public function test_health_check_updates_status(): void
    {
        Sanctum::actingAs($this->admin);
        Http::fake([
            'https://api.health.example.com/*' => Http::response(['ok' => true], 200),
            'https://api.health.example.com' => Http::response(['ok' => true], 200),
        ]);

        $environment = ApplicationEnvironment::query()->create([
            'application_id' => $this->application->id,
            'name' => 'Testing',
            'slug' => 'testing',
            'type' => 'testing',
            'api_url' => 'https://api.health.example.com',
            'status' => 'active',
            'health_status' => 'unknown',
            'is_current' => false,
        ]);

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/environments/'.$environment->uuid.'/health-check')
            ->assertOk()
            ->assertJsonPath('data.check.success', true)
            ->assertJsonPath('data.environment.health_status', 'healthy');
    }

    public function test_duplicate_environment_type_rejected(): void
    {
        Sanctum::actingAs($this->admin);

        ApplicationEnvironment::query()->create([
            'application_id' => $this->application->id,
            'name' => 'Sandbox',
            'slug' => 'sandbox',
            'type' => 'sandbox',
            'status' => 'active',
            'health_status' => 'unknown',
        ]);

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/environments', [
            'name' => 'Sandbox 2',
            'type' => 'sandbox',
        ])->assertStatus(422);
    }

    public function test_guest_cannot_access_environments(): void
    {
        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/environments/dashboard')
            ->assertUnauthorized();
    }
}

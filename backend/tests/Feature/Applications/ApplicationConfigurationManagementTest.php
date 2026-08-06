<?php

namespace Tests\Feature\Applications;

use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationConfiguration;
use App\Domains\Applications\Models\ApplicationEnvironment;
use App\Domains\Companies\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApplicationConfigurationManagementTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'config-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $company = Company::query()->create([
            'company_name' => 'Config Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->application = Application::query()->create([
            'company_id' => $company->id,
            'name' => 'Config App',
            'slug' => 'config-app',
            'platform' => 'android',
            'category' => 'business',
            'status' => 'active',
            'visibility' => 'private',
        ]);
    }

    public function test_can_create_feature_flags_and_toggle(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/applications/'.$this->application->uuid.'/configurations', [
            'type' => 'feature_flags',
            'name' => 'Feature Flags',
            'payload' => [
                'flags' => [
                    ['key' => 'new_checkout', 'enabled' => false, 'description' => 'New checkout', 'rollout' => 0],
                ],
            ],
            'status' => 'published',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.configuration.type', 'feature_flags')
            ->assertJsonPath('data.configuration.version', 1);

        $uuid = $create->json('data.configuration.uuid');

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/configurations/'.$uuid.'/feature-flags/new_checkout/toggle', [
            'enabled' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.configuration.payload.flags.0.enabled', true)
            ->assertJsonPath('data.configuration.version', 2);
    }

    public function test_validation_endpoint_rejects_invalid_payload(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/configurations/validate', [
            'type' => 'maintenance_mode',
            'payload' => [
                'enabled' => 'yes',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.valid', false);
    }

    public function test_sensitive_keys_are_masked_and_history_is_recorded(): void
    {
        Sanctum::actingAs($this->admin);

        $environment = ApplicationEnvironment::query()->create([
            'application_id' => $this->application->id,
            'name' => 'Production',
            'slug' => 'production',
            'type' => 'production',
            'status' => 'active',
            'health_status' => 'unknown',
        ]);

        $create = $this->postJson('/api/v1/applications/'.$this->application->uuid.'/configurations', [
            'type' => 'firebase_keys',
            'environment_id' => $environment->uuid,
            'payload' => [
                'api_key' => 'firebase-secret',
                'project_id' => 'demo-project',
                'app_id' => '1:123:web:abc',
                'messaging_sender_id' => '123456',
                'storage_bucket' => 'demo.appspot.com',
            ],
            'status' => 'published',
        ])->assertCreated();

        $this->assertStringNotContainsString('firebase-secret', $create->getContent());
        $this->assertSame('********', $create->json('data.configuration.payload.api_key'));

        $uuid = $create->json('data.configuration.uuid');

        $this->putJson('/api/v1/applications/'.$this->application->uuid.'/configurations/'.$uuid, [
            'payload' => [
                'api_key' => '********',
                'project_id' => 'demo-project-2',
                'app_id' => '1:123:web:abc',
                'messaging_sender_id' => '********',
                'storage_bucket' => 'demo.appspot.com',
            ],
            'change_summary' => 'Updated project id',
        ])->assertOk()->assertJsonPath('data.configuration.version', 2);

        $history = $this->getJson('/api/v1/applications/'.$this->application->uuid.'/configurations/'.$uuid.'/history')
            ->assertOk()
            ->json('data.history');

        $this->assertGreaterThanOrEqual(2, count($history));

        $firstHistoryUuid = $history[count($history) - 1]['uuid'];
        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/configurations/'.$uuid.'/history/'.$firstHistoryUuid.'/restore')
            ->assertOk()
            ->assertJsonPath('data.configuration.payload.project_id', 'demo-project');
    }

    public function test_duplicate_type_for_same_scope_rejected(): void
    {
        Sanctum::actingAs($this->admin);

        ApplicationConfiguration::query()->create([
            'application_id' => $this->application->id,
            'environment_id' => null,
            'type' => 'remote_config',
            'name' => 'Remote Config',
            'payload' => ['values' => ['theme' => 'light']],
            'status' => 'draft',
            'version' => 1,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/configurations', [
            'type' => 'remote_config',
            'payload' => ['values' => []],
        ])->assertStatus(422);
    }
}

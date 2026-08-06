<?php

namespace Tests\Feature\Applications;

use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Models\Integration;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApplicationManagementTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'application-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Apps Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_guest_cannot_list_applications(): void
    {
        $this->getJson('/api/v1/applications')->assertUnauthorized();
    }

    public function test_admin_can_create_list_and_view_application(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/applications', [
            'company_id' => $this->company->uuid,
            'name' => 'Customer Portal',
            'description' => 'Mobile customer experience app',
            'platform' => 'android',
            'category' => 'business',
            'current_version' => '1.0.0',
            'minimum_supported_version' => '1.0.0',
            'status' => 'active',
            'visibility' => 'internal',
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.application.name', 'Customer Portal')
            ->assertJsonPath('data.application.slug', 'customer-portal')
            ->assertJsonPath('data.application.platform', 'android')
            ->assertJsonPath('data.application.category', 'business')
            ->assertJsonPath('data.application.visibility', 'internal');

        $uuid = $create->json('data.application.uuid');

        $this->getJson('/api/v1/applications?search=Customer')
            ->assertOk()
            ->assertJsonPath('data.applications.meta.total', 1);

        $this->getJson('/api/v1/applications/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.application.current_version', '1.0.0')
            ->assertJsonPath('data.application.company.uuid', $this->company->uuid);
    }

    public function test_application_validation_rejects_invalid_payload(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/applications', [
            'company_id' => '',
            'name' => '',
            'platform' => 'blackberry',
            'category' => 'unknown',
            'status' => 'published',
            'visibility' => 'secret',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['company_id', 'name', 'platform', 'category', 'status', 'visibility']]);
    }

    public function test_admin_can_update_soft_delete_and_restore_application(): void
    {
        Sanctum::actingAs($this->admin);

        $application = Application::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Legacy App',
            'slug' => 'legacy-app',
            'platform' => 'ios',
            'category' => 'utilities',
            'status' => 'draft',
            'visibility' => 'private',
        ]);

        $this->putJson('/api/v1/applications/'.$application->uuid, [
            'name' => 'Legacy App Updated',
            'status' => 'inactive',
            'current_version' => '2.1.0',
        ])
            ->assertOk()
            ->assertJsonPath('data.application.name', 'Legacy App Updated')
            ->assertJsonPath('data.application.status', 'inactive')
            ->assertJsonPath('data.application.current_version', '2.1.0');

        $this->deleteJson('/api/v1/applications/'.$application->uuid)->assertOk();
        $this->assertSoftDeleted('applications', ['id' => $application->id]);

        $this->postJson('/api/v1/applications/'.$application->uuid.'/restore')
            ->assertOk()
            ->assertJsonPath('data.application.uuid', $application->uuid);
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/applications')->assertForbidden();
    }

    public function test_slug_is_unique_per_company(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/applications', [
            'company_id' => $this->company->uuid,
            'name' => 'Field Service',
            'slug' => 'field-service',
            'platform' => 'web',
        ])->assertCreated();

        $second = $this->postJson('/api/v1/applications', [
            'company_id' => $this->company->uuid,
            'name' => 'Field Service',
            'slug' => 'field-service',
            'platform' => 'android',
        ])->assertCreated();

        $this->assertSame('field-service-2', $second->json('data.application.slug'));
    }

    public function test_integration_must_belong_to_same_company(): void
    {
        Sanctum::actingAs($this->admin);

        $otherCompany = Company::query()->create([
            'company_name' => 'Other Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $integration = Integration::query()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Foreign API',
            'slug' => 'foreign-api',
            'type' => 'rest_api',
            'status' => 'active',
            'authentication_type' => 'api_key',
            'health_status' => 'unknown',
            'timeout' => 30,
            'retry_attempts' => 3,
        ]);

        $this->postJson('/api/v1/applications', [
            'company_id' => $this->company->uuid,
            'integration_id' => $integration->uuid,
            'name' => 'Linked App',
            'platform' => 'ios',
        ])->assertStatus(422);
    }

    public function test_can_link_integration_from_same_company(): void
    {
        Sanctum::actingAs($this->admin);

        $integration = Integration::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Push Provider',
            'slug' => 'push-provider',
            'type' => 'rest_api',
            'status' => 'active',
            'authentication_type' => 'bearer_token',
            'health_status' => 'unknown',
            'timeout' => 30,
            'retry_attempts' => 3,
        ]);

        $this->postJson('/api/v1/applications', [
            'company_id' => $this->company->uuid,
            'integration_id' => $integration->uuid,
            'name' => 'Push Enabled App',
            'platform' => 'android',
            'status' => 'active',
        ])
            ->assertCreated()
            ->assertJsonPath('data.application.integration.uuid', $integration->uuid);
    }
}

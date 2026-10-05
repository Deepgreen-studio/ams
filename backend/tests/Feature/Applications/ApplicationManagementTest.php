<?php

namespace Tests\Feature\Applications;

use App\Domains\Applications\Enums\ApplicationPermission;
use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Roles\Models\Role;
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
            'slug' => 'customer-portal',
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
            ->assertJsonPath('data.applications.meta.total', 1)
            ->assertJsonStructure([
                'data' => [
                    'statistics' => ['total', 'active', 'draft', 'inactive', 'archived', 'trashed'],
                ],
            ]);

        $this->getJson('/api/v1/applications/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.application.current_version', '1.0.0')
            ->assertJsonPath('data.application.company.uuid', $this->company->uuid);
    }

    public function test_other_category_requires_a_custom_name(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/applications', [
            'company_id' => $this->company->uuid,
            'name' => 'Custom Category App',
            'slug' => 'custom-category-app',
            'platform' => 'web',
            'category' => 'other',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['category_custom']]);

        $this->postJson('/api/v1/applications', [
            'company_id' => $this->company->uuid,
            'name' => 'Custom Category App',
            'slug' => 'custom-category-app',
            'platform' => 'web',
            'category' => 'other',
            'category_custom' => 'Logistics',
        ])
            ->assertCreated()
            ->assertJsonPath('data.application.category', 'other')
            ->assertJsonPath('data.application.category_custom', 'Logistics')
            ->assertJsonPath('data.application.category_label', 'Logistics');
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
            ->assertJsonStructure(['errors' => ['company_id', 'name', 'slug', 'platform', 'category', 'status', 'visibility']]);
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

        $this->getJson('/api/v1/applications?trashed=only')
            ->assertOk();
    }

    public function test_soft_deleted_applications_require_their_own_permission(): void
    {
        $companyAdmin = Role::findByName('company-admin', 'web');
        $this->assertTrue($companyAdmin->hasPermissionTo(ApplicationPermission::DELETE));
        $this->assertTrue($companyAdmin->hasPermissionTo(ApplicationPermission::VIEW_TRASH));
        $this->assertTrue($companyAdmin->hasPermissionTo(ApplicationPermission::RESTORE));
        $this->assertTrue($companyAdmin->hasPermissionTo(ApplicationPermission::FORCE_DELETE));
        $this->assertDatabaseHas('permissions', [
            'name' => ApplicationPermission::VIEW_TRASH,
            'display_name' => 'Soft Deleted View',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => ApplicationPermission::DELETE,
            'display_name' => 'Soft Delete Application',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => ApplicationPermission::RESTORE,
            'display_name' => 'Restore Application',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => ApplicationPermission::FORCE_DELETE,
            'display_name' => 'Permanent Delete Application',
        ]);

        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->getJson('/api/v1/applications?trashed=only')->assertForbidden();
        $this->deleteJson('/api/v1/applications/'.$this->company->uuid)->assertForbidden();
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

        $this->postJson('/api/v1/applications', [
            'company_id' => $this->company->uuid,
            'name' => 'Field Service',
            'slug' => 'field-service-web',
            'platform' => 'android',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'The name has already been taken.');

        $this->postJson('/api/v1/applications', [
            'company_id' => $this->company->uuid,
            'name' => 'Field Service Mobile',
            'slug' => 'field-service',
            'platform' => 'ios',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.slug.0', 'The slug has already been taken.');
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
            'slug' => 'linked-app',
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
            'slug' => 'push-enabled-app',
            'platform' => 'android',
            'status' => 'active',
        ])
            ->assertCreated()
            ->assertJsonPath('data.application.integration.uuid', $integration->uuid);
    }

    public function test_only_force_delete_permission_can_permanently_delete_an_application(): void
    {
        $application = Application::factory()->forCompany($this->company)->create([
            'name' => 'Gone App',
        ]);

        Sanctum::actingAs($this->admin);
        $this->deleteJson('/api/v1/applications/'.$application->uuid.'/force-delete')->assertStatus(422);

        $application->delete();

        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);
        $this->deleteJson('/api/v1/applications/'.$application->uuid.'/force-delete')->assertForbidden();

        Sanctum::actingAs($this->admin);
        $this->deleteJson('/api/v1/applications/'.$application->uuid.'/force-delete')
            ->assertOk()
            ->assertJsonPath('message', 'Application permanently deleted.');

        $this->assertDatabaseMissing('applications', ['id' => $application->id]);
    }
}

<?php

namespace Tests\Feature\Companies;

use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationEnvironment;
use App\Domains\Applications\Models\ApplicationRelease;
use App\Domains\Applications\Models\ApplicationVersion;
use App\Domains\Companies\Models\Company;
use App\Domains\Companies\Models\Department;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'company-admin@example.com']);
        $this->admin->assignRole('super-admin');
    }

    public function test_guest_cannot_list_companies(): void
    {
        $this->getJson('/api/v1/companies')->assertUnauthorized();
    }

    public function test_admin_can_create_list_and_view_company(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/companies', [
            'company_name' => 'Acme Corp',
            'legal_name' => 'Acme Corporation Ltd',
            'registration_number' => 'REG-1001',
            'email' => 'hello@acme.test',
            'phone' => '+12025550123',
            'address' => '1 Main Street',
            'city' => 'Austin',
            'state' => 'Texas',
            'postal_code' => '78701',
            'website' => 'https://acme.test',
            'country' => 'US',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.company.company_name', 'Acme Corp')
            ->assertJsonPath('success', true);

        $uuid = $create->json('data.company.uuid');

        $this->getJson('/api/v1/companies?search=Acme')
            ->assertOk()
            ->assertJsonPath('data.companies.meta.total', 1);

        $this->getJson('/api/v1/companies/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.company.registration_number', 'REG-1001');
    }

    public function test_company_validation_rejects_invalid_payload(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/companies', [
            'company_name' => '',
            'email' => 'not-an-email',
            'website' => 'not-a-url',
            'currency' => 'US',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['company_name', 'registration_number', 'email', 'phone', 'address', 'city', 'state', 'postal_code', 'country', 'website', 'currency']]);
    }

    public function test_admin_can_update_soft_delete_and_restore_company(): void
    {
        Sanctum::actingAs($this->admin);
        $company = Company::query()->create([
            'company_name' => 'Delete Me Inc',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->putJson('/api/v1/companies/'.$company->uuid, [
            'company_name' => 'Updated Inc',
            'status' => 'inactive',
        ])
            ->assertOk()
            ->assertJsonPath('data.company.company_name', 'Updated Inc');

        $this->assertDatabaseHas('activity_log', [
            'event' => 'status_changed',
            'subject_id' => $company->id,
        ]);
        $this->assertDatabaseHas('system_events', [
            'event' => 'company.status_changed',
            'level' => 'warning',
            'module' => 'companies',
        ]);

        $this->getJson('/api/v1/companies/'.$company->uuid.'/activity')
            ->assertOk()
            ->assertJsonFragment(['action' => 'status_changed'])
            ->assertJsonFragment(['event' => 'company.status_changed', 'level' => 'warning']);

        $department = Department::query()->create([
            'company_id' => $company->id,
            'name' => 'Engineering',
            'status' => 'active',
        ]);

        $this->deleteJson('/api/v1/companies/'.$company->uuid)->assertOk();
        $this->assertSoftDeleted('companies', ['id' => $company->id]);
        $this->assertSoftDeleted('departments', ['id' => $department->id]);

        $this->getJson('/api/v1/companies?trashed=only')
            ->assertOk()
            ->assertJsonPath('data.companies.items.0.uuid', $company->uuid);

        $this->postJson('/api/v1/companies/'.$company->uuid.'/restore')
            ->assertOk()
            ->assertJsonPath('data.company.uuid', $company->uuid);

        $this->assertNotSoftDeleted('departments', ['id' => $department->id]);
    }

    public function test_admin_can_manage_departments_teams_and_locations(): void
    {
        Sanctum::actingAs($this->admin);
        $company = Company::query()->create([
            'company_name' => 'Org Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $department = $this->postJson('/api/v1/departments', [
            'company_id' => $company->uuid,
            'name' => 'Engineering',
            'description' => 'Product engineering',
        ])->assertCreated()->json('data.department');

        $team = $this->postJson('/api/v1/teams', [
            'company_id' => $company->uuid,
            'department_id' => $department['uuid'],
            'manager_id' => $this->admin->uuid,
            'name' => 'Platform',
        ])->assertCreated()->json('data.team');

        $this->assertSame('Platform', $team['name']);

        $location = $this->postJson('/api/v1/company-locations', [
            'company_id' => $company->uuid,
            'branch_name' => 'HQ',
            'city' => 'Austin',
            'country' => 'US',
            'is_headquarters' => true,
        ])->assertCreated()->json('data.location');

        $this->assertTrue($location['is_headquarters']);

        $this->getJson('/api/v1/departments?company='.$company->uuid)
            ->assertOk()
            ->assertJsonPath('data.departments.items.0.teams.0.name', 'Platform')
            ->assertJsonPath('data.departments.items.0.status', 'active');
        $this->getJson('/api/v1/departments?search=Engineer')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Engineering']);

        $this->postJson('/api/v1/departments', [
            'company_id' => $company->uuid,
            'name' => 'Alpha',
        ])->assertCreated();

        $sorted = $this->getJson('/api/v1/departments?company='.$company->uuid.'&sort_by=name&sort_dir=asc')
            ->assertOk()
            ->json('data.departments.items');
        $this->assertSame(['Alpha', 'Engineering'], array_column($sorted, 'name'));

        $byDate = $this->getJson('/api/v1/departments?company='.$company->uuid.'&sort_by=created_at&sort_dir=desc')
            ->assertOk()
            ->json('data.departments.items');
        $this->assertSame('Alpha', $byDate[0]['name']);
        $this->getJson('/api/v1/departments/'.$department['uuid'])
            ->assertOk()
            ->assertJsonPath('data.department.uuid', $department['uuid'])
            ->assertJsonPath('data.department.company.company_name', 'Org Co')
            ->assertJsonPath('data.department.teams.0.name', 'Platform');
        $this->getJson('/api/v1/teams?company='.$company->uuid)->assertOk();
        $this->getJson('/api/v1/company-locations?company='.$company->uuid)->assertOk();

        $this->putJson('/api/v1/departments/'.$department['uuid'], ['name' => 'R&D'])->assertOk();
        $this->deleteJson('/api/v1/teams/'.$team['uuid'])->assertOk();
        $this->deleteJson('/api/v1/company-locations/'.$location['uuid'])->assertOk();
        $this->deleteJson('/api/v1/departments/'.$department['uuid'])->assertOk();
    }

    public function test_admin_can_upload_logo_and_update_branding(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->admin);

        $company = Company::query()->create([
            'company_name' => 'Brand Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->post('/api/v1/companies/'.$company->uuid.'/logo', [
            'file' => $file,
        ], ['Accept' => 'application/json']);

        $response->assertOk()->assertJsonPath('success', true);
        Storage::disk('public')->assertExists($response->json('data.company.logo'));

        $this->putJson('/api/v1/companies/'.$company->uuid.'/branding', [
            'primary_color' => '#2563eb',
            'secondary_color' => '#0f172a',
            'business_hours' => [
                'monday' => ['09:00', '17:00'],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.company.primary_color', '#2563eb');
    }

    public function test_manager_without_create_permission_cannot_create_company(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/companies', [
            'company_name' => 'Blocked Co',
        ])->assertForbidden();
    }

    public function test_registration_number_must_be_unique(): void
    {
        Sanctum::actingAs($this->admin);
        Company::query()->create([
            'company_name' => 'First',
            'registration_number' => 'DUP-1',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->postJson('/api/v1/companies', [
            'company_name' => 'Second',
            'registration_number' => 'DUP-1',
            'email' => 'second@example.test',
            'phone' => '+12025550199',
            'address' => '2 Main Street',
            'city' => 'Austin',
            'state' => 'Texas',
            'postal_code' => '78701',
            'country' => 'US',
            'currency' => 'USD',
        ])->assertStatus(422)
            ->assertJsonPath('errors.registration_number.0', 'The company code has already been taken.');
    }

    public function test_company_console_summarizes_operations(): void
    {
        Sanctum::actingAs($this->admin);
        $company = Company::query()->create([
            'company_name' => 'Console Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $application = Application::factory()->forCompany($company)->create([
            'name' => 'Console App',
            'platform' => 'android',
            'status' => 'active',
            'current_version' => '2.1.0',
        ]);
        ApplicationEnvironment::factory()->forApplication($application)->create([
            'name' => 'Production',
            'type' => 'production',
            'status' => 'active',
            'health_status' => 'healthy',
        ]);
        $version = ApplicationVersion::factory()->forApplication($application)->production()->create([
            'version_number' => '2.1.0',
        ]);
        ApplicationRelease::factory()->forVersion($version)->create([
            'name' => 'September release',
            'status' => 'deployed',
        ]);
        SupportTicket::factory()->forApplication($application)->open()->create([
            'subject' => 'Login fails on Android',
            'priority' => 'high',
        ]);
        Integration::query()->create([
            'company_id' => $company->id,
            'name' => 'Billing API',
            'slug' => 'billing-api',
            'type' => 'rest_api',
            'status' => 'active',
            'authentication_type' => 'api_key',
            'health_status' => 'healthy',
        ]);
        Notification::factory()->create([
            'company_id' => $company->id,
            'user_id' => $this->admin->id,
            'title' => 'Release deployed',
            'status' => 'sent',
        ]);

        $this->getJson('/api/v1/companies/'.$company->uuid.'/console')
            ->assertOk()
            ->assertJsonPath('data.console.company.name', 'Console Co')
            ->assertJsonPath('data.console.kpis.0.value', 1)
            ->assertJsonPath('data.console.applications.0.name', 'Console App')
            ->assertJsonPath('data.console.environments.0.health_status', 'healthy')
            ->assertJsonPath('data.console.versions.0.version_number', '2.1.0')
            ->assertJsonPath('data.console.releases.0.name', 'September release')
            ->assertJsonPath('data.console.support_issues.0.subject', 'Login fails on Android')
            ->assertJsonPath('data.console.integrations.0.name', 'Billing API')
            ->assertJsonPath('data.console.notifications.0.title', 'Release deployed')
            ->assertJsonPath('data.console.platforms.0.platform', 'android')
            ->assertJsonPath('data.console.platforms.0.active', 1)
            ->assertJsonPath('data.console.profile.display_name', 'Console Co')
            ->assertJsonPath('data.console.profile.timezone', 'UTC')
            ->assertJsonPath('data.console.sections.1.key', 'applications');
    }

    public function test_inactive_company_cascades_to_children_and_isolates_non_members(): void
    {
        Sanctum::actingAs($this->admin);
        $company = Company::query()->create([
            'company_name' => 'Hold Co',
            'status' => 'active',
            'timezone' => 'Asia/Kolkata',
            'language' => 'en',
            'currency' => 'INR',
        ]);
        $application = Application::factory()->forCompany($company)->create([
            'status' => 'active',
            'platform' => 'web',
        ]);
        $user = User::factory()->create();
        $company->users()->attach($user->id, ['is_primary' => true, 'status' => 'active']);
        SupportTicket::factory()->forApplication($application)->open()->create();
        Integration::query()->create([
            'company_id' => $company->id,
            'name' => 'Hold API',
            'slug' => 'hold-api',
            'type' => 'rest_api',
            'status' => 'active',
            'authentication_type' => 'api_key',
            'health_status' => 'healthy',
        ]);

        $this->putJson('/api/v1/companies/'.$company->uuid, ['status' => 'suspended'])->assertOk();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'inactive',
            'company_status_hold' => true,
            'held_status' => 'active',
        ]);
        $this->assertDatabaseHas('integrations', [
            'company_id' => $company->id,
            'status' => 'inactive',
            'company_status_hold' => true,
        ]);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'status' => 'suspended',
            'company_status_hold' => true,
        ]);
        $this->assertDatabaseHas('support_tickets', [
            'company_id' => $company->id,
            'status' => 'pending',
            'company_status_hold' => true,
            'held_status' => 'open',
        ]);

        $this->putJson('/api/v1/companies/'.$company->uuid, ['status' => 'active'])->assertOk();
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'active',
            'company_status_hold' => false,
        ]);

        $outsider = User::factory()->create();
        $outsider->assignRole('manager');
        Sanctum::actingAs($outsider);
        $this->getJson('/api/v1/companies/'.$company->uuid.'/console')->assertNotFound();
    }
}

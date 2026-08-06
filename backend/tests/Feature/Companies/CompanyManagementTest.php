<?php

namespace Tests\Feature\Companies;

use App\Domains\Companies\Models\Company;
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
            ->assertJsonStructure(['errors' => ['company_name', 'email', 'website', 'currency']]);
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

        $this->deleteJson('/api/v1/companies/'.$company->uuid)->assertOk();
        $this->assertSoftDeleted('companies', ['id' => $company->id]);

        $this->postJson('/api/v1/companies/'.$company->uuid.'/restore')
            ->assertOk()
            ->assertJsonPath('data.company.uuid', $company->uuid);
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

        $this->getJson('/api/v1/departments?company='.$company->uuid)->assertOk();
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
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['registration_number']]);
    }
}

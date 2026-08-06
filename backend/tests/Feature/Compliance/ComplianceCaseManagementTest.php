<?php

namespace Tests\Feature\Compliance;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Models\ComplianceCase;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComplianceCaseManagementTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'compliance-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Compliance Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_guest_cannot_list_compliance_cases(): void
    {
        $this->getJson('/api/v1/compliance/cases')->assertUnauthorized();
    }

    public function test_admin_can_create_list_and_view_compliance_case(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/compliance/cases', [
            'company_id' => $this->company->uuid,
            'title' => 'GDPR Data Subject Access Review',
            'description' => 'Review DSARs for Q3 reporting period.',
            'case_type' => 'gdpr',
            'priority' => 'high',
            'status' => 'open',
            'due_date' => now()->addDays(14)->toDateString(),
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.case.title', 'GDPR Data Subject Access Review')
            ->assertJsonPath('data.case.case_type', 'gdpr')
            ->assertJsonPath('data.case.priority', 'high')
            ->assertJsonPath('data.case.status', 'open');

        $this->assertNotEmpty($create->json('data.case.case_number'));
        $uuid = $create->json('data.case.uuid');

        $this->getJson('/api/v1/compliance/cases?search=GDPR')
            ->assertOk()
            ->assertJsonPath('data.cases.meta.total', 1);

        $this->getJson('/api/v1/compliance/cases/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.case.company.uuid', $this->company->uuid)
            ->assertJsonPath('data.case.case_type_label', 'GDPR');
    }

    public function test_compliance_case_validation_rejects_invalid_payload(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/compliance/cases', [
            'company_id' => '',
            'title' => '',
            'case_type' => 'invalid',
            'priority' => 'urgent',
            'status' => 'published',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['company_id', 'title', 'case_type', 'priority', 'status']]);
    }

    public function test_admin_can_update_soft_delete_and_restore_compliance_case(): void
    {
        Sanctum::actingAs($this->admin);

        $case = ComplianceCase::query()->create([
            'company_id' => $this->company->id,
            'case_number' => 'CMP-20260804-00001',
            'title' => 'SOC 2 Control Gap',
            'description' => 'Initial review',
            'case_type' => 'soc2',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $this->putJson('/api/v1/compliance/cases/'.$case->uuid, [
            'title' => 'SOC 2 Control Gap Updated',
            'status' => 'completed',
            'priority' => 'critical',
        ])
            ->assertOk()
            ->assertJsonPath('data.case.title', 'SOC 2 Control Gap Updated')
            ->assertJsonPath('data.case.status', 'completed')
            ->assertJsonPath('data.case.priority', 'critical');

        $this->assertNotNull($case->fresh()->completed_at);

        $this->deleteJson('/api/v1/compliance/cases/'.$case->uuid)->assertOk();
        $this->assertSoftDeleted('compliance_cases', ['id' => $case->id]);

        $this->postJson('/api/v1/compliance/cases/'.$case->uuid.'/restore')
            ->assertOk()
            ->assertJsonPath('data.case.uuid', $case->uuid);
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/compliance/cases')->assertForbidden();
        $this->getJson('/api/v1/compliance/dashboard')->assertForbidden();
    }

    public function test_compliance_officer_can_access_dashboard(): void
    {
        $officer = User::factory()->create(['email' => 'compliance-officer@example.com']);
        $officer->assignRole('compliance-officer');
        Sanctum::actingAs($officer);

        ComplianceCase::factory()->forCompany($this->company)->create([
            'title' => 'Open privacy review',
            'case_type' => 'privacy_request',
            'priority' => 'critical',
            'status' => 'open',
        ]);

        $this->getJson('/api/v1/compliance/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'statistics' => ['total', 'open', 'active', 'critical'],
                    'recent_active',
                    'elevated',
                ],
            ]);
    }

    public function test_case_can_be_assigned_to_user(): void
    {
        Sanctum::actingAs($this->admin);

        $assignee = User::factory()->create(['email' => 'assignee@example.com']);

        $create = $this->postJson('/api/v1/compliance/cases', [
            'company_id' => $this->company->uuid,
            'title' => 'Assigned ISO review',
            'case_type' => 'iso_27001',
            'assigned_to' => $assignee->uuid,
            'priority' => 'high',
        ])->assertCreated();

        $this->assertSame($assignee->id, $create->json('data.case.assigned_to'));
        $this->assertSame($assignee->uuid, $create->json('data.case.assignee.uuid'));
    }

    public function test_case_numbers_are_sequential_per_day(): void
    {
        Sanctum::actingAs($this->admin);

        $first = $this->postJson('/api/v1/compliance/cases', [
            'company_id' => $this->company->uuid,
            'title' => 'Case One',
            'case_type' => 'audit_compliance',
        ])->assertCreated();

        $second = $this->postJson('/api/v1/compliance/cases', [
            'company_id' => $this->company->uuid,
            'title' => 'Case Two',
            'case_type' => 'risk_register',
        ])->assertCreated();

        $prefix = 'CMP-'.now()->format('Ymd').'-';
        $this->assertStringStartsWith($prefix, $first->json('data.case.case_number'));
        $this->assertStringStartsWith($prefix, $second->json('data.case.case_number'));
        $this->assertNotSame(
            $first->json('data.case.case_number'),
            $second->json('data.case.case_number')
        );
    }
}

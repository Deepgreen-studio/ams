<?php

namespace Tests\Feature\Compliance;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Models\DpiaAssessment;
use App\Domains\Compliance\Models\RiskRegister;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DpiaManagementTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'dpia-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'DPIA Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_guest_cannot_list_dpia(): void
    {
        $this->getJson('/api/v1/compliance/dpia')->assertUnauthorized();
    }

    public function test_admin_can_create_wizard_submit_and_approve_dpia(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/compliance/dpia', [
            'company_id' => $this->company->uuid,
            'title' => 'Customer analytics platform DPIA',
            'template_code' => 'high_risk_processing',
            'processing_purpose' => 'Behavioral analytics',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.assessment.status', 'draft')
            ->assertJsonPath('data.assessment.template_code', 'high_risk_processing');

        $uuid = $create->json('data.assessment.uuid');
        $this->assertStringStartsWith('DPIA-', $create->json('data.assessment.assessment_number'));

        $this->postJson('/api/v1/compliance/dpia/'.$uuid.'/wizard', [
            'wizard_step' => 3,
            'wizard_payload' => ['rights' => 'Data subject access supported'],
            'data_categories' => ['email', 'usage'],
            'overall_risk_score' => 12,
            'mitigation_summary' => 'Access controls and retention limits',
        ])
            ->assertOk()
            ->assertJsonPath('data.assessment.status', 'in_progress')
            ->assertJsonPath('data.assessment.overall_risk_level', 'high');

        $this->postJson('/api/v1/compliance/dpia/'.$uuid.'/submit', [])
            ->assertOk()
            ->assertJsonPath('data.assessment.status', 'pending_review');

        $this->postJson('/api/v1/compliance/dpia/'.$uuid.'/approve', [
            'approval_notes' => 'Residual risk acceptable',
            'next_review_at' => now()->addYear()->toDateString(),
        ])
            ->assertOk()
            ->assertJsonPath('data.assessment.status', 'approved');
    }

    public function test_risk_register_scoring_and_mitigation_actions(): void
    {
        Sanctum::actingAs($this->admin);

        $dpia = DpiaAssessment::factory()->forCompany($this->company)->create();

        $create = $this->postJson('/api/v1/compliance/dpia/risks', [
            'company_id' => $this->company->uuid,
            'dpia_assessment_id' => $dpia->uuid,
            'title' => 'Unauthorized profiling',
            'category' => 'privacy',
            'likelihood' => 3,
            'impact' => 4,
            'mitigation_plan' => 'Purpose limitation controls',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.risk.risk_score', 12)
            ->assertJsonPath('data.risk.risk_level', 'high');

        $riskUuid = $create->json('data.risk.uuid');
        $this->assertStringStartsWith('RSK-', $create->json('data.risk.risk_number'));

        $this->postJson('/api/v1/compliance/dpia/risks/'.$riskUuid.'/assess', [
            'likelihood' => 4,
            'impact' => 5,
            'residual_likelihood' => 2,
            'residual_impact' => 3,
            'mitigation_plan' => 'Updated mitigation plan',
        ])
            ->assertOk()
            ->assertJsonPath('data.risk.risk_score', 20)
            ->assertJsonPath('data.risk.residual_score', 6);

        $action = $this->postJson('/api/v1/compliance/dpia/risks/'.$riskUuid.'/actions', [
            'title' => 'Implement retention purge job',
            'action_type' => 'mitigation',
            'due_at' => now()->addDays(14)->toDateString(),
        ]);

        $action->assertCreated();
        $actionUuid = $action->json('data.action.uuid');

        $this->postJson('/api/v1/compliance/dpia/risks/'.$riskUuid.'/actions/'.$actionUuid.'/complete')
            ->assertOk()
            ->assertJsonPath('data.action.status', 'completed');

        $this->getJson('/api/v1/compliance/dpia/mitigation')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_dashboard_risk_matrix_and_templates(): void
    {
        Sanctum::actingAs($this->admin);

        RiskRegister::factory()->forCompany($this->company)->high()->create([
            'likelihood' => 4,
            'impact' => 4,
            'risk_score' => 16,
            'risk_level' => 'high',
        ]);

        $this->getJson('/api/v1/compliance/dpia/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'dpia_statistics',
                    'risk_statistics',
                    'recent_assessments',
                    'pending_approval',
                    'mitigation_queue',
                ],
            ]);

        $this->getJson('/api/v1/compliance/dpia/risk-matrix')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/compliance/dpia/templates')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_rejected_dpia_requires_notes(): void
    {
        Sanctum::actingAs($this->admin);

        $dpia = DpiaAssessment::factory()->forCompany($this->company)->pendingReview()->create();

        $this->postJson('/api/v1/compliance/dpia/'.$dpia->uuid.'/reject', [])
            ->assertStatus(422);

        $this->postJson('/api/v1/compliance/dpia/'.$dpia->uuid.'/reject', [
            'rejection_notes' => 'Incomplete risk scoring',
        ])
            ->assertOk()
            ->assertJsonPath('data.assessment.status', 'rejected');
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/compliance/dpia')->assertForbidden();
    }
}

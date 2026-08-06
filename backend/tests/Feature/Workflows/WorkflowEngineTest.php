<?php

namespace Tests\Feature\Workflows;

use App\Domains\Companies\Models\Company;
use App\Domains\Workflows\Enums\WorkflowDefinitionStatus;
use App\Domains\Workflows\Enums\WorkflowInstanceStatus;
use App\Domains\Workflows\Enums\WorkflowStepType;
use App\Domains\Workflows\Enums\WorkflowType;
use App\Domains\Workflows\Models\Workflow;
use App\Domains\Workflows\Models\WorkflowInstance;
use App\Domains\Workflows\Models\WorkflowLog;
use App\Domains\Workflows\Models\WorkflowStep;
use App\Domains\Workflows\Services\WorkflowEngineService;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkflowEngineTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'workflow-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Workflow Tenant Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_admin_can_create_and_publish_workflow(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/workflows', [
            'name' => 'Purchase Approval',
            'description' => 'Manager approval for purchases',
            'type' => WorkflowType::Approval->value,
            'steps' => [
                [
                    'name' => 'Start',
                    'step_key' => 'start',
                    'step_type' => WorkflowStepType::Start->value,
                    'position_x' => 40,
                    'position_y' => 100,
                    'next_step_keys' => ['approve'],
                ],
                [
                    'name' => 'Approve',
                    'step_key' => 'approve',
                    'step_type' => WorkflowStepType::Approval->value,
                    'position_x' => 260,
                    'position_y' => 100,
                    'config' => [
                        'approver_roles' => ['super-admin'],
                        'timeout_minutes' => 60,
                    ],
                    'on_approve_step_key' => 'end',
                    'on_reject_step_key' => 'end',
                ],
                [
                    'name' => 'End',
                    'step_key' => 'end',
                    'step_type' => WorkflowStepType::End->value,
                    'position_x' => 480,
                    'position_y' => 100,
                    'config' => ['outcome' => 'approved'],
                ],
            ],
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.workflow.name', 'Purchase Approval');

        $uuid = $create->json('data.workflow.uuid');

        $this->postJson("/api/v1/workflows/{$uuid}/publish")
            ->assertOk()
            ->assertJsonPath('data.workflow.status', WorkflowDefinitionStatus::Active->value);

        $this->assertDatabaseHas('workflows', [
            'name' => 'Purchase Approval',
            'status' => WorkflowDefinitionStatus::Active->value,
        ]);
        $this->assertDatabaseCount('workflow_steps', 3);
    }

    public function test_workflow_instance_can_be_started_and_approved(): void
    {
        Sanctum::actingAs($this->admin);

        $workflow = $this->makeApprovalWorkflow();

        $start = $this->postJson("/api/v1/workflows/{$workflow->uuid}/start", [
            'subject_type' => 'purchase_request',
            'subject_id' => 'PR-100',
            'subject_label' => 'Laptop purchase',
            'company_id' => $this->company->uuid,
            'context' => ['amount' => 1200],
        ]);

        $start->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.instance.status', WorkflowInstanceStatus::InProgress->value);

        $instanceUuid = $start->json('data.instance.uuid');

        $this->assertDatabaseHas('workflow_logs', [
            'action' => 'started',
        ]);

        $this->postJson("/api/v1/workflows/instances/{$instanceUuid}/approve", [
            'comment' => 'Looks good',
        ])->assertOk()
            ->assertJsonPath('data.instance.status', WorkflowInstanceStatus::Approved->value);

        $this->assertTrue(
            WorkflowLog::query()->where('action', 'approved')->exists()
        );
    }

    public function test_workflow_rejection_closes_instance(): void
    {
        Sanctum::actingAs($this->admin);
        $workflow = $this->makeApprovalWorkflow();

        $instance = app(WorkflowEngineService::class)->start($workflow, [
            'subject_label' => 'Reject me',
            'company_id' => $this->company->id,
        ], $this->admin);

        $this->postJson("/api/v1/workflows/instances/{$instance->uuid}/reject", [
            'comment' => 'Missing docs',
        ])->assertOk()
            ->assertJsonPath('data.instance.status', WorkflowInstanceStatus::Rejected->value);
    }

    public function test_timeout_escalates_when_configured(): void
    {
        $workflow = $this->makeApprovalWorkflow(escalate: true);
        $engine = app(WorkflowEngineService::class);

        $instance = $engine->start($workflow, [
            'subject_label' => 'Escalate me',
            'company_id' => $this->company->id,
        ], $this->admin);

        WorkflowInstance::query()->whereKey($instance->id)->update([
            'due_at' => now()->subMinute(),
        ]);

        $processed = $engine->processTimeouts();
        $this->assertSame(1, $processed);

        $fresh = $instance->fresh();
        $this->assertNull($fresh->completed_at);
        $this->assertTrue(
            collect($fresh->pending_approvers)->contains(fn ($item) => ($item['value'] ?? null) === 'super-admin')
        );
        $this->assertTrue(WorkflowLog::query()->where('action', 'escalated')->exists());
    }

    public function test_dashboard_monitor_and_history_endpoints(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/workflows/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/workflows/monitor')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/workflows/history')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/workflows/queue')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    private function makeApprovalWorkflow(bool $escalate = false): Workflow
    {
        $workflow = Workflow::query()->create([
            'name' => 'Test Approval '.uniqid(),
            'type' => WorkflowType::Approval->value,
            'status' => WorkflowDefinitionStatus::Active->value,
            'is_enabled' => true,
            'version' => 1,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'name' => 'Start',
            'step_key' => 'start',
            'step_type' => WorkflowStepType::Start->value,
            'sort_order' => 0,
            'position_x' => 40,
            'position_y' => 100,
            'next_step_keys' => ['approve'],
            'config' => [],
        ]);

        WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'name' => 'Approve',
            'step_key' => 'approve',
            'step_type' => WorkflowStepType::Approval->value,
            'sort_order' => 1,
            'position_x' => 260,
            'position_y' => 100,
            'config' => [
                'approver_roles' => ['super-admin'],
                'timeout_minutes' => 30,
                'escalate_to_role' => $escalate ? 'super-admin' : null,
                'approvals_required' => 1,
            ],
            'on_approve_step_key' => 'end_approved',
            'on_reject_step_key' => 'end_rejected',
            'next_step_keys' => ['end_approved'],
        ]);

        WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'name' => 'Approved',
            'step_key' => 'end_approved',
            'step_type' => WorkflowStepType::End->value,
            'sort_order' => 2,
            'position_x' => 480,
            'position_y' => 40,
            'config' => ['outcome' => 'approved'],
            'next_step_keys' => [],
        ]);

        WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'name' => 'Rejected',
            'step_key' => 'end_rejected',
            'step_type' => WorkflowStepType::End->value,
            'sort_order' => 3,
            'position_x' => 480,
            'position_y' => 180,
            'config' => ['outcome' => 'rejected'],
            'next_step_keys' => [],
        ]);

        return $workflow->fresh(['steps']);
    }
}

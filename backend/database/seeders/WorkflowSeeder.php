<?php

namespace Database\Seeders;

use App\Domains\Workflows\Enums\WorkflowDefinitionStatus;
use App\Domains\Workflows\Enums\WorkflowStepType;
use App\Domains\Workflows\Enums\WorkflowType;
use App\Domains\Workflows\Models\Workflow;
use App\Domains\Workflows\Models\WorkflowStep;
use App\Models\User;
use Illuminate\Database\Seeder;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $actor = User::query()->orderBy('id')->first();

        $this->seedApprovalWorkflow($actor?->id);
        $this->seedSequentialBusinessWorkflow($actor?->id);
        $this->seedParallelReviewWorkflow($actor?->id);
    }

    private function seedApprovalWorkflow(?int $actorId): void
    {
        $workflow = Workflow::query()->firstOrCreate(
            ['name' => 'Standard Approval Workflow'],
            [
                'description' => 'Sequential manager approval with timeout escalation.',
                'type' => WorkflowType::Approval->value,
                'status' => WorkflowDefinitionStatus::Active->value,
                'is_enabled' => true,
                'version' => 1,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ],
        );

        if ($workflow->steps()->exists()) {
            return;
        }

        $this->createSteps($workflow, [
            [
                'name' => 'Start',
                'step_key' => 'start',
                'step_type' => WorkflowStepType::Start->value,
                'sort_order' => 0,
                'position_x' => 40,
                'position_y' => 140,
                'next_step_keys' => ['manager_approval'],
            ],
            [
                'name' => 'Manager Approval',
                'step_key' => 'manager_approval',
                'step_type' => WorkflowStepType::Approval->value,
                'sort_order' => 1,
                'position_x' => 280,
                'position_y' => 140,
                'config' => [
                    'approver_roles' => ['manager', 'company-admin', 'super-admin'],
                    'timeout_minutes' => 1440,
                    'escalate_to_role' => 'super-admin',
                    'approvals_required' => 1,
                ],
                'on_approve_step_key' => 'end_approved',
                'on_reject_step_key' => 'end_rejected',
                'next_step_keys' => ['end_approved'],
            ],
            [
                'name' => 'Approved',
                'step_key' => 'end_approved',
                'step_type' => WorkflowStepType::End->value,
                'sort_order' => 2,
                'position_x' => 520,
                'position_y' => 60,
                'config' => ['outcome' => 'approved'],
            ],
            [
                'name' => 'Rejected',
                'step_key' => 'end_rejected',
                'step_type' => WorkflowStepType::End->value,
                'sort_order' => 3,
                'position_x' => 520,
                'position_y' => 220,
                'config' => ['outcome' => 'rejected'],
            ],
        ]);
    }

    private function seedSequentialBusinessWorkflow(?int $actorId): void
    {
        $workflow = Workflow::query()->firstOrCreate(
            ['name' => 'Customer Onboarding Workflow'],
            [
                'description' => 'Business sequential stages: intake → compliance check → activation.',
                'type' => WorkflowType::Sequential->value,
                'status' => WorkflowDefinitionStatus::Active->value,
                'is_enabled' => true,
                'version' => 1,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ],
        );

        if ($workflow->steps()->exists()) {
            return;
        }

        $this->createSteps($workflow, [
            [
                'name' => 'Start',
                'step_key' => 'start',
                'step_type' => WorkflowStepType::Start->value,
                'sort_order' => 0,
                'position_x' => 40,
                'position_y' => 140,
                'next_step_keys' => ['intake'],
            ],
            [
                'name' => 'Intake',
                'step_key' => 'intake',
                'step_type' => WorkflowStepType::Task->value,
                'sort_order' => 1,
                'position_x' => 240,
                'position_y' => 140,
                'config' => ['auto_complete' => true],
                'next_step_keys' => ['compliance_check'],
            ],
            [
                'name' => 'Compliance Check',
                'step_key' => 'compliance_check',
                'step_type' => WorkflowStepType::Condition->value,
                'sort_order' => 2,
                'position_x' => 440,
                'position_y' => 140,
                'config' => [
                    'logic' => 'and',
                    'rules' => [
                        ['field' => 'compliance_ready', 'operator' => 'equals', 'value' => '1'],
                    ],
                    'on_true_step_key' => 'activation_approval',
                    'on_false_step_key' => 'end_hold',
                ],
            ],
            [
                'name' => 'Activation Approval',
                'step_key' => 'activation_approval',
                'step_type' => WorkflowStepType::Approval->value,
                'sort_order' => 3,
                'position_x' => 660,
                'position_y' => 80,
                'config' => [
                    'approver_roles' => ['company-admin', 'super-admin'],
                    'timeout_minutes' => 720,
                ],
                'on_approve_step_key' => 'end_done',
                'on_reject_step_key' => 'end_hold',
            ],
            [
                'name' => 'Completed',
                'step_key' => 'end_done',
                'step_type' => WorkflowStepType::End->value,
                'sort_order' => 4,
                'position_x' => 880,
                'position_y' => 40,
            ],
            [
                'name' => 'On Hold',
                'step_key' => 'end_hold',
                'step_type' => WorkflowStepType::End->value,
                'sort_order' => 5,
                'position_x' => 880,
                'position_y' => 220,
                'config' => ['outcome' => 'cancelled'],
            ],
        ]);
    }

    private function seedParallelReviewWorkflow(?int $actorId): void
    {
        $workflow = Workflow::query()->firstOrCreate(
            ['name' => 'Parallel Dual Review'],
            [
                'description' => 'Parallel gateway splitting to dual review branches.',
                'type' => WorkflowType::Parallel->value,
                'status' => WorkflowDefinitionStatus::Active->value,
                'is_enabled' => true,
                'version' => 1,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ],
        );

        if ($workflow->steps()->exists()) {
            return;
        }

        $this->createSteps($workflow, [
            [
                'name' => 'Start',
                'step_key' => 'start',
                'step_type' => WorkflowStepType::Start->value,
                'sort_order' => 0,
                'position_x' => 40,
                'position_y' => 160,
                'next_step_keys' => ['split'],
            ],
            [
                'name' => 'Parallel Split',
                'step_key' => 'split',
                'step_type' => WorkflowStepType::ParallelGateway->value,
                'sort_order' => 1,
                'position_x' => 240,
                'position_y' => 160,
                'config' => ['join_step_key' => 'end'],
                'next_step_keys' => ['legal_review', 'finance_review'],
            ],
            [
                'name' => 'Legal Review',
                'step_key' => 'legal_review',
                'step_type' => WorkflowStepType::Approval->value,
                'sort_order' => 2,
                'position_x' => 460,
                'position_y' => 60,
                'config' => [
                    'approver_roles' => ['compliance-officer', 'super-admin'],
                    'timeout_minutes' => 2880,
                ],
                'on_approve_step_key' => 'end',
                'on_reject_step_key' => 'end',
            ],
            [
                'name' => 'Finance Review',
                'step_key' => 'finance_review',
                'step_type' => WorkflowStepType::Approval->value,
                'sort_order' => 3,
                'position_x' => 460,
                'position_y' => 260,
                'config' => [
                    'approver_roles' => ['manager', 'super-admin'],
                    'timeout_minutes' => 2880,
                ],
                'on_approve_step_key' => 'end',
                'on_reject_step_key' => 'end',
            ],
            [
                'name' => 'End',
                'step_key' => 'end',
                'step_type' => WorkflowStepType::End->value,
                'sort_order' => 4,
                'position_x' => 700,
                'position_y' => 160,
            ],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $steps
     */
    private function createSteps(Workflow $workflow, array $steps): void
    {
        foreach ($steps as $step) {
            WorkflowStep::query()->create(array_merge([
                'workflow_id' => $workflow->id,
                'config' => [],
                'next_step_keys' => [],
                'is_required' => true,
            ], $step));
        }
    }
}

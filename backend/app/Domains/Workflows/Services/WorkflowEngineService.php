<?php

namespace App\Domains\Workflows\Services;

use App\Domains\Workflows\Enums\WorkflowDefinitionStatus;
use App\Domains\Workflows\Enums\WorkflowInstanceStatus;
use App\Domains\Workflows\Enums\WorkflowLogAction;
use App\Domains\Workflows\Enums\WorkflowStepType;
use App\Domains\Workflows\Models\Workflow;
use App\Domains\Workflows\Models\WorkflowInstance;
use App\Domains\Workflows\Models\WorkflowStep;
use App\Domains\Workflows\Repositories\WorkflowInstanceRepository;
use App\Domains\Workflows\Repositories\WorkflowLogRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class WorkflowEngineService
{
    public function __construct(
        private readonly WorkflowInstanceRepository $instanceRepository,
        private readonly WorkflowLogRepository $logRepository,
        private readonly WorkflowConditionEvaluator $conditionEvaluator,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function start(Workflow $workflow, array $payload, User $actor): WorkflowInstance
    {
        if ($workflow->status !== WorkflowDefinitionStatus::Active || ! $workflow->is_enabled) {
            throw new ApiException('Only active enabled workflows can be started.', 422);
        }

        $workflow->loadMissing('steps');
        $startStep = $workflow->steps->first(fn (WorkflowStep $step) => $step->step_type === WorkflowStepType::Start)
            ?? $workflow->steps->sortBy('sort_order')->first();

        if (! $startStep) {
            throw new ApiException('Workflow has no steps to start.', 422);
        }

        return DB::transaction(function () use ($workflow, $payload, $actor, $startStep): WorkflowInstance {
            /** @var WorkflowInstance $instance */
            $instance = $this->instanceRepository->create([
                'workflow_id' => $workflow->id,
                'company_id' => $payload['company_id'] ?? $workflow->company_id,
                'subject_type' => $payload['subject_type'] ?? null,
                'subject_id' => isset($payload['subject_id']) ? (string) $payload['subject_id'] : null,
                'subject_label' => $payload['subject_label'] ?? $workflow->name,
                'status' => WorkflowInstanceStatus::InProgress->value,
                'current_step_id' => $startStep->id,
                'active_step_keys' => [$startStep->step_key],
                'pending_approvers' => [],
                'context' => $payload['context'] ?? [],
                'metadata' => $payload['metadata'] ?? [],
                'started_at' => now(),
                'started_by' => $actor->id,
            ]);

            $this->writeLog($instance, $startStep, WorkflowLogAction::Started, $actor, null, WorkflowInstanceStatus::InProgress->value, 'Workflow instance started.');

            $this->enterStep($instance->fresh(['workflow.steps', 'currentStep']), $startStep, $actor, autoAdvanceStart: true);

            return $this->instanceRepository->findByIdentifierOrFail($instance->uuid)
                ->load(['workflow.steps', 'currentStep', 'logs.actor', 'logs.step', 'starter']);
        });
    }

    public function approve(WorkflowInstance $instance, User $actor, ?string $comment = null): WorkflowInstance
    {
        return $this->decide($instance, $actor, approved: true, comment: $comment);
    }

    public function reject(WorkflowInstance $instance, User $actor, ?string $comment = null): WorkflowInstance
    {
        return $this->decide($instance, $actor, approved: false, comment: $comment);
    }

    public function cancel(WorkflowInstance $instance, User $actor, ?string $comment = null): WorkflowInstance
    {
        $this->assertOpen($instance);

        return DB::transaction(function () use ($instance, $actor, $comment): WorkflowInstance {
            $from = $instance->status?->value ?? (string) $instance->status;
            $this->instanceRepository->update($instance->id, [
                'status' => WorkflowInstanceStatus::Cancelled->value,
                'completed_at' => now(),
                'pending_approvers' => [],
                'due_at' => null,
            ]);
            $this->writeLog($instance, $instance->currentStep, WorkflowLogAction::Cancelled, $actor, $from, WorkflowInstanceStatus::Cancelled->value, $comment ?? 'Workflow cancelled.');

            return $this->freshInstance($instance->uuid);
        });
    }

    public function processTimeouts(int $limit = 50): int
    {
        $processed = 0;

        foreach ($this->instanceRepository->dueForTimeout($limit) as $instance) {
            try {
                $this->handleTimeout($instance);
                $processed++;
            } catch (Throwable $exception) {
                Log::warning('Workflow timeout processing failed', [
                    'instance' => $instance->uuid,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    private function handleTimeout(WorkflowInstance $instance): void
    {
        $this->assertOpen($instance);
        $step = $instance->currentStep;
        $escalateRole = $step?->escalateToRole();

        DB::transaction(function () use ($instance, $step, $escalateRole): void {
            $from = $instance->status?->value ?? (string) $instance->status;

            if ($escalateRole) {
                $pending = [['type' => 'role', 'value' => $escalateRole]];
                $timeout = $step?->timeoutMinutes();
                $this->instanceRepository->update($instance->id, [
                    'pending_approvers' => $pending,
                    'due_at' => $timeout ? now()->addMinutes($timeout) : null,
                    'metadata' => array_merge($instance->metadata ?? [], [
                        'escalated_at' => now()->toIso8601String(),
                        'escalated_to_role' => $escalateRole,
                    ]),
                ]);
                $this->writeLog($instance, $step, WorkflowLogAction::Escalated, null, $from, $from, 'Step timed out and escalated to '.$escalateRole, [
                    'escalate_to_role' => $escalateRole,
                ]);

                return;
            }

            $this->instanceRepository->update($instance->id, [
                'status' => WorkflowInstanceStatus::TimedOut->value,
                'completed_at' => now(),
                'pending_approvers' => [],
                'due_at' => null,
            ]);
            $this->writeLog($instance, $step, WorkflowLogAction::TimedOut, null, $from, WorkflowInstanceStatus::TimedOut->value, 'Workflow timed out.');
        });
    }

    private function decide(WorkflowInstance $instance, User $actor, bool $approved, ?string $comment): WorkflowInstance
    {
        $this->assertOpen($instance);
        $instance->loadMissing(['workflow.steps', 'currentStep']);
        $step = $instance->currentStep;

        if (! $step || $step->step_type !== WorkflowStepType::Approval) {
            throw new ApiException('Current step is not an approval step.', 422);
        }

        if (! $this->actorCanApprove($instance, $actor)) {
            throw new ApiException('You are not an assigned approver for this step.', 403);
        }

        return DB::transaction(function () use ($instance, $actor, $approved, $comment, $step): WorkflowInstance {
            $from = $instance->status?->value ?? (string) $instance->status;
            $action = $approved ? WorkflowLogAction::Approved : WorkflowLogAction::Rejected;
            $this->writeLog($instance, $step, $action, $actor, $from, $from, $comment);

            if (! $approved) {
                $rejectKey = $step->on_reject_step_key;
                if ($rejectKey) {
                    $next = $this->findStepByKey($instance->workflow, $rejectKey);
                    if ($next) {
                        $this->enterStep($instance->fresh(['workflow.steps']), $next, $actor);

                        return $this->freshInstance($instance->uuid);
                    }
                }

                $this->instanceRepository->update($instance->id, [
                    'status' => WorkflowInstanceStatus::Rejected->value,
                    'completed_at' => now(),
                    'pending_approvers' => [],
                    'due_at' => null,
                ]);
                $this->writeLog($instance, $step, WorkflowLogAction::Completed, $actor, $from, WorkflowInstanceStatus::Rejected->value, 'Workflow rejected.');

                return $this->freshInstance($instance->uuid);
            }

            $approvalsRequired = (int) ($step->config['approvals_required'] ?? 1);
            $decisions = $instance->metadata['step_approvals'][$step->step_key] ?? [];
            $decisions[] = ['user_uuid' => $actor->uuid, 'at' => now()->toIso8601String()];
            $metadata = $instance->metadata ?? [];
            $metadata['step_approvals'][$step->step_key] = $decisions;

            $this->instanceRepository->update($instance->id, ['metadata' => $metadata]);

            if (count($decisions) < $approvalsRequired) {
                $pending = collect($instance->pending_approvers ?? [])
                    ->reject(fn ($item) => ($item['type'] ?? null) === 'user' && ($item['value'] ?? null) === $actor->uuid)
                    ->values()
                    ->all();
                $this->instanceRepository->update($instance->id, ['pending_approvers' => $pending]);

                return $this->freshInstance($instance->uuid);
            }

            $nextKey = $step->on_approve_step_key
                ?? (is_array($step->next_step_keys) ? ($step->next_step_keys[0] ?? null) : null);

            if (! $nextKey) {
                $this->complete($instance, $actor, WorkflowInstanceStatus::Approved);

                return $this->freshInstance($instance->uuid);
            }

            $next = $this->findStepByKey($instance->workflow, $nextKey);
            if (! $next) {
                throw new ApiException("Next step [{$nextKey}] not found.", 422);
            }

            $this->enterStep($instance->fresh(['workflow.steps']), $next, $actor);

            return $this->freshInstance($instance->uuid);
        });
    }

    private function enterStep(WorkflowInstance $instance, WorkflowStep $step, ?User $actor, bool $autoAdvanceStart = false): void
    {
        $from = $instance->status?->value ?? (string) $instance->status;
        $timeout = $step->timeoutMinutes();
        $pending = $this->buildPendingApprovers($step);

        $this->instanceRepository->update($instance->id, [
            'current_step_id' => $step->id,
            'active_step_keys' => [$step->step_key],
            'pending_approvers' => $pending,
            'due_at' => $timeout ? now()->addMinutes($timeout) : null,
            'status' => WorkflowInstanceStatus::InProgress->value,
        ]);

        $this->writeLog($instance, $step, WorkflowLogAction::Advanced, $actor, $from, WorkflowInstanceStatus::InProgress->value, 'Entered step: '.$step->name, [
            'step_key' => $step->step_key,
            'step_type' => $step->step_type?->value ?? $step->step_type,
        ]);

        if ($step->step_type === WorkflowStepType::End) {
            $outcome = (string) ($step->config['outcome'] ?? '');
            $terminal = match ($outcome) {
                'rejected' => WorkflowInstanceStatus::Rejected,
                'cancelled' => WorkflowInstanceStatus::Cancelled,
                'approved' => WorkflowInstanceStatus::Approved,
                default => (($instance->workflow->type?->value ?? null) === 'approval')
                    ? WorkflowInstanceStatus::Approved
                    : WorkflowInstanceStatus::Completed,
            };
            $this->complete($instance->fresh(), $actor, $terminal);

            return;
        }

        if ($step->step_type === WorkflowStepType::Start && $autoAdvanceStart) {
            $nextKey = is_array($step->next_step_keys) ? ($step->next_step_keys[0] ?? null) : null;
            if ($nextKey) {
                $next = $this->findStepByKey($instance->workflow, $nextKey);
                if ($next) {
                    $this->enterStep($instance->fresh(['workflow.steps']), $next, $actor);
                }
            }

            return;
        }

        if ($step->step_type === WorkflowStepType::Condition) {
            $passes = $this->conditionEvaluator->passes($step->config ?? [], $instance->context ?? []);
            $nextKey = $passes
                ? ($step->config['on_true_step_key'] ?? $step->on_approve_step_key ?? (is_array($step->next_step_keys) ? ($step->next_step_keys[0] ?? null) : null))
                : ($step->config['on_false_step_key'] ?? $step->on_reject_step_key ?? null);

            if ($nextKey) {
                $next = $this->findStepByKey($instance->workflow, $nextKey);
                if ($next) {
                    $this->enterStep($instance->fresh(['workflow.steps']), $next, $actor);
                }
            }

            return;
        }

        if ($step->step_type === WorkflowStepType::ParallelGateway) {
            $branchKeys = $step->next_step_keys ?? [];
            $active = [];
            foreach ($branchKeys as $key) {
                $branch = $this->findStepByKey($instance->workflow, (string) $key);
                if ($branch) {
                    $active[] = $branch->step_key;
                }
            }

            $first = $branchKeys[0] ?? null;
            $firstStep = $first ? $this->findStepByKey($instance->workflow, (string) $first) : null;
            $this->instanceRepository->update($instance->id, [
                'active_step_keys' => $active,
                'current_step_id' => $firstStep?->id ?? $step->id,
                'pending_approvers' => $firstStep ? $this->buildPendingApprovers($firstStep) : [],
                'metadata' => array_merge($instance->metadata ?? [], [
                    'parallel_branches' => $active,
                    'parallel_join_key' => $step->config['join_step_key'] ?? null,
                ]),
            ]);

            return;
        }

        if ($step->step_type === WorkflowStepType::Task) {
            // Task stages wait for explicit advance via approve (treated as complete) or auto if configured.
            if (! empty($step->config['auto_complete'])) {
                $nextKey = is_array($step->next_step_keys) ? ($step->next_step_keys[0] ?? null) : null;
                if ($nextKey) {
                    $next = $this->findStepByKey($instance->workflow, $nextKey);
                    if ($next) {
                        $this->enterStep($instance->fresh(['workflow.steps']), $next, $actor);
                    }
                }
            }
        }
    }

    private function complete(WorkflowInstance $instance, ?User $actor, WorkflowInstanceStatus $status): void
    {
        $from = $instance->status?->value ?? (string) $instance->status;
        $this->instanceRepository->update($instance->id, [
            'status' => $status->value,
            'completed_at' => now(),
            'pending_approvers' => [],
            'due_at' => null,
        ]);
        $this->writeLog($instance, $instance->currentStep, WorkflowLogAction::Completed, $actor, $from, $status->value, 'Workflow completed.');
    }

    /**
     * @return list<array{type: string, value: string}>
     */
    private function buildPendingApprovers(WorkflowStep $step): array
    {
        $pending = [];
        foreach ($step->approverUserUuids() as $uuid) {
            $pending[] = ['type' => 'user', 'value' => $uuid];
        }
        foreach ($step->approverRoleNames() as $role) {
            $pending[] = ['type' => 'role', 'value' => $role];
        }

        return $pending;
    }

    private function actorCanApprove(WorkflowInstance $instance, User $actor): bool
    {
        $pending = $instance->pending_approvers ?? [];
        if ($pending === []) {
            return $actor->can('workflows.approve') || $actor->can('workflows.manage');
        }

        foreach ($pending as $item) {
            if (($item['type'] ?? null) === 'user' && ($item['value'] ?? null) === $actor->uuid) {
                return true;
            }
            if (($item['type'] ?? null) === 'role' && $actor->hasRole((string) ($item['value'] ?? ''))) {
                return true;
            }
        }

        return $actor->can('workflows.manage');
    }

    private function findStepByKey(Workflow $workflow, string $key): ?WorkflowStep
    {
        $workflow->loadMissing('steps');

        return $workflow->steps->first(fn (WorkflowStep $step) => $step->step_key === $key);
    }

    private function assertOpen(WorkflowInstance $instance): void
    {
        $status = $instance->status instanceof WorkflowInstanceStatus
            ? $instance->status
            : WorkflowInstanceStatus::tryFrom((string) $instance->status);

        if ($status?->isTerminal()) {
            throw new ApiException('Workflow instance is already closed.', 422);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeLog(
        WorkflowInstance $instance,
        ?WorkflowStep $step,
        WorkflowLogAction $action,
        ?User $actor,
        ?string $from,
        ?string $to,
        ?string $comment = null,
        array $payload = [],
    ): void {
        $this->logRepository->create([
            'workflow_instance_id' => $instance->id,
            'workflow_step_id' => $step?->id,
            'action' => $action->value,
            'actor_id' => $actor?->id,
            'from_status' => $from,
            'to_status' => $to,
            'comment' => $comment,
            'payload' => $payload ?: null,
        ]);
    }

    private function freshInstance(string $uuid): WorkflowInstance
    {
        return $this->instanceRepository->findByIdentifierOrFail($uuid)
            ->load(['workflow.steps', 'currentStep', 'logs.actor', 'logs.step', 'starter', 'company']);
    }
}

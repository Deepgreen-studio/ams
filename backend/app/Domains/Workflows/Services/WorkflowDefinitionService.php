<?php

namespace App\Domains\Workflows\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Workflows\Enums\WorkflowDefinitionStatus;
use App\Domains\Workflows\Enums\WorkflowStepType;
use App\Domains\Workflows\Enums\WorkflowType;
use App\Domains\Workflows\Events\WorkflowCreated;
use App\Domains\Workflows\Events\WorkflowDeleted;
use App\Domains\Workflows\Events\WorkflowUpdated;
use App\Domains\Workflows\Models\Workflow;
use App\Domains\Workflows\Models\WorkflowStep;
use App\Domains\Workflows\Repositories\WorkflowInstanceRepository;
use App\Domains\Workflows\Repositories\WorkflowRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkflowDefinitionService
{
    public function __construct(
        private readonly WorkflowRepository $workflowRepository,
        private readonly WorkflowInstanceRepository $instanceRepository,
        private readonly CompanyRepository $companyRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['company_id']) && ! is_numeric($filters['company_id'])) {
            $filters['company_id'] = $this->companyRepository
                ->findByIdentifierOrFail((string) $filters['company_id'])->id;
        }

        return $this->workflowRepository->paginateFiltered($filters);
    }

    public function find(string $identifier): Workflow
    {
        return $this->workflowRepository->findByIdentifierOrFail($identifier)
            ->load([
                'company:id,uuid,company_name',
                'steps',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        return [
            'statistics' => $this->workflowRepository->statistics(),
            'instance_statistics' => $this->instanceRepository->statistics(),
            'catalog' => $this->catalog(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function catalog(): array
    {
        return [
            'types' => collect(WorkflowType::cases())->map(fn ($item) => [
                'value' => $item->value,
                'label' => $item->label(),
            ])->values()->all(),
            'statuses' => collect(WorkflowDefinitionStatus::cases())->map(fn ($item) => [
                'value' => $item->value,
                'label' => $item->label(),
            ])->values()->all(),
            'step_types' => collect(WorkflowStepType::cases())->map(fn ($item) => [
                'value' => $item->value,
                'label' => $item->label(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Workflow
    {
        return DB::transaction(function () use ($data, $actor): Workflow {
            $payload = $this->preparePayload($data);
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;
            $payload['version'] = 1;

            /** @var Workflow $workflow */
            $workflow = $this->workflowRepository->create($payload);
            $this->syncSteps($workflow, $data['steps'] ?? $this->defaultSteps($payload['type']));

            event(new WorkflowCreated($workflow->fresh(['steps']), $actor));

            return $this->find($workflow->uuid);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): Workflow
    {
        return DB::transaction(function () use ($identifier, $data, $actor): Workflow {
            $workflow = $this->workflowRepository->findByIdentifierOrFail($identifier);
            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;
            $payload['version'] = (int) $workflow->version + 1;

            /** @var Workflow $updated */
            $updated = $this->workflowRepository->update($workflow->id, $payload);

            if (array_key_exists('steps', $data)) {
                $this->syncSteps($updated, $data['steps'] ?? []);
            }

            event(new WorkflowUpdated($updated->fresh(['steps']), $actor));

            return $this->find($updated->uuid);
        });
    }

    public function toggle(string $identifier, User $actor, ?bool $enabled = null): Workflow
    {
        $workflow = $this->workflowRepository->findByIdentifierOrFail($identifier);
        $next = $enabled ?? ! $workflow->is_enabled;

        /** @var Workflow $updated */
        $updated = $this->workflowRepository->update($workflow->id, [
            'is_enabled' => $next,
            'updated_by' => $actor->id,
        ]);

        event(new WorkflowUpdated($updated, $actor));

        return $this->find($updated->uuid);
    }

    public function publish(string $identifier, User $actor): Workflow
    {
        $workflow = $this->find($identifier);
        if ($workflow->steps->isEmpty()) {
            throw new ApiException('Cannot publish a workflow without steps.', 422);
        }

        /** @var Workflow $updated */
        $updated = $this->workflowRepository->update($workflow->id, [
            'status' => WorkflowDefinitionStatus::Active->value,
            'is_enabled' => true,
            'updated_by' => $actor->id,
        ]);

        event(new WorkflowUpdated($updated, $actor));

        return $this->find($updated->uuid);
    }

    public function archive(string $identifier, User $actor): Workflow
    {
        $workflow = $this->workflowRepository->findByIdentifierOrFail($identifier);

        /** @var Workflow $updated */
        $updated = $this->workflowRepository->update($workflow->id, [
            'status' => WorkflowDefinitionStatus::Archived->value,
            'is_enabled' => false,
            'updated_by' => $actor->id,
        ]);

        event(new WorkflowUpdated($updated, $actor));

        return $this->find($updated->uuid);
    }

    public function delete(string $identifier, User $actor): void
    {
        $workflow = $this->workflowRepository->findByIdentifierOrFail($identifier);
        $this->workflowRepository->delete($workflow->id);
        event(new WorkflowDeleted($workflow, $actor));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePayload(array $data, bool $isUpdate = false): array
    {
        $payload = [];

        foreach (['name', 'description', 'metadata', 'is_enabled'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        if (array_key_exists('type', $data)) {
            $payload['type'] = WorkflowType::from((string) $data['type'])->value;
        } elseif (! $isUpdate) {
            $payload['type'] = WorkflowType::Approval->value;
        }

        if (array_key_exists('status', $data)) {
            $payload['status'] = WorkflowDefinitionStatus::from((string) $data['status'])->value;
        } elseif (! $isUpdate) {
            $payload['status'] = WorkflowDefinitionStatus::Draft->value;
        }

        if (array_key_exists('company_id', $data)) {
            $payload['company_id'] = blank($data['company_id'])
                ? null
                : $this->companyRepository->findByIdentifierOrFail((string) $data['company_id'])->id;
        }

        if (! array_key_exists('is_enabled', $payload) && ! $isUpdate) {
            $payload['is_enabled'] = true;
        }

        return $payload;
    }

    /**
     * @param  list<array<string, mixed>>  $steps
     */
    private function syncSteps(Workflow $workflow, array $steps): void
    {
        $workflow->steps()->delete();

        if ($steps === []) {
            throw new ApiException('At least one workflow step is required.', 422);
        }

        foreach (array_values($steps) as $index => $item) {
            $stepType = WorkflowStepType::from((string) ($item['step_type'] ?? WorkflowStepType::Task->value));
            $stepKey = (string) ($item['step_key'] ?? Str::slug((string) ($item['name'] ?? 'step-'.$index), '_'));

            WorkflowStep::query()->create([
                'workflow_id' => $workflow->id,
                'name' => (string) ($item['name'] ?? 'Step '.($index + 1)),
                'step_key' => $stepKey,
                'step_type' => $stepType->value,
                'sort_order' => (int) ($item['sort_order'] ?? $index),
                'position_x' => (int) ($item['position_x'] ?? ($index * 220)),
                'position_y' => (int) ($item['position_y'] ?? 120),
                'config' => $item['config'] ?? [],
                'next_step_keys' => $item['next_step_keys'] ?? [],
                'on_approve_step_key' => $item['on_approve_step_key'] ?? null,
                'on_reject_step_key' => $item['on_reject_step_key'] ?? null,
                'is_required' => array_key_exists('is_required', $item) ? (bool) $item['is_required'] : true,
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaultSteps(string $type): array
    {
        return [
            [
                'name' => 'Start',
                'step_key' => 'start',
                'step_type' => WorkflowStepType::Start->value,
                'sort_order' => 0,
                'position_x' => 40,
                'position_y' => 120,
                'next_step_keys' => ['manager_approval'],
            ],
            [
                'name' => 'Manager Approval',
                'step_key' => 'manager_approval',
                'step_type' => WorkflowStepType::Approval->value,
                'sort_order' => 1,
                'position_x' => 280,
                'position_y' => 120,
                'config' => [
                    'approver_roles' => ['manager', 'company-admin'],
                    'timeout_minutes' => 1440,
                    'escalate_to_role' => 'super-admin',
                    'approvals_required' => 1,
                ],
                'on_approve_step_key' => 'end',
                'on_reject_step_key' => 'end_rejected',
                'next_step_keys' => ['end'],
            ],
            [
                'name' => 'Approved End',
                'step_key' => 'end',
                'step_type' => WorkflowStepType::End->value,
                'sort_order' => 2,
                'position_x' => 520,
                'position_y' => 60,
                'next_step_keys' => [],
            ],
            [
                'name' => 'Rejected End',
                'step_key' => 'end_rejected',
                'step_type' => WorkflowStepType::End->value,
                'sort_order' => 3,
                'position_x' => 520,
                'position_y' => 200,
                'config' => ['outcome' => 'rejected'],
                'next_step_keys' => [],
            ],
        ];
    }
}

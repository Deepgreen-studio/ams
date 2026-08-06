<?php

namespace App\Domains\Workflows\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Workflows\Models\WorkflowInstance;
use App\Domains\Workflows\Repositories\WorkflowInstanceRepository;
use App\Domains\Workflows\Repositories\WorkflowLogRepository;
use App\Domains\Workflows\Repositories\WorkflowRepository;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkflowInstanceService
{
    public function __construct(
        private readonly WorkflowInstanceRepository $instanceRepository,
        private readonly WorkflowLogRepository $logRepository,
        private readonly WorkflowRepository $workflowRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly WorkflowEngineService $engineService,
        private readonly WorkflowDefinitionService $definitionService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['workflow']) && ! is_numeric($filters['workflow'] ?? null)) {
            $workflow = $this->workflowRepository->findByIdentifierOrFail((string) $filters['workflow']);
            $filters['workflow_id'] = $workflow->id;
        }

        if (! empty($filters['company_id']) && ! is_numeric($filters['company_id'])) {
            $filters['company_id'] = $this->companyRepository
                ->findByIdentifierOrFail((string) $filters['company_id'])->id;
        }

        return $this->instanceRepository->paginateFiltered($filters);
    }

    public function find(string $identifier): WorkflowInstance
    {
        return $this->instanceRepository->findByIdentifierOrFail($identifier)
            ->load([
                'workflow.steps',
                'currentStep',
                'starter:id,uuid,full_name,email',
                'company:id,uuid,company_name',
                'logs' => fn ($q) => $q->with(['actor:id,uuid,full_name,email', 'step:id,uuid,name,step_key,step_type'])->latest('id'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function approvalQueue(User $actor, array $filters = []): LengthAwarePaginator
    {
        $roles = $actor->getRoleNames()->all();

        return $this->instanceRepository->paginateApprovalQueue($actor->uuid, $roles, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateHistory(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['instance'])) {
            $instance = $this->instanceRepository->findByIdentifierOrFail((string) $filters['instance']);
            $filters['workflow_instance_id'] = $instance->id;
        }

        return $this->logRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function start(string $workflowIdentifier, array $payload, User $actor): WorkflowInstance
    {
        $workflow = $this->definitionService->find($workflowIdentifier);

        if (! empty($payload['company_id']) && ! is_numeric($payload['company_id'])) {
            $payload['company_id'] = $this->companyRepository
                ->findByIdentifierOrFail((string) $payload['company_id'])->id;
        }

        return $this->engineService->start($workflow, $payload, $actor);
    }

    public function approve(string $identifier, User $actor, ?string $comment = null): WorkflowInstance
    {
        return $this->engineService->approve($this->find($identifier), $actor, $comment);
    }

    public function reject(string $identifier, User $actor, ?string $comment = null): WorkflowInstance
    {
        return $this->engineService->reject($this->find($identifier), $actor, $comment);
    }

    public function cancel(string $identifier, User $actor, ?string $comment = null): WorkflowInstance
    {
        return $this->engineService->cancel($this->find($identifier), $actor, $comment);
    }

    /**
     * @return array<string, mixed>
     */
    public function monitor(): array
    {
        return [
            'statistics' => $this->instanceRepository->statistics(),
            'recent' => $this->instanceRepository->paginateFiltered(['per_page' => 8])->items(),
        ];
    }
}

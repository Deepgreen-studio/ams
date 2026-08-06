<?php

namespace App\Domains\Compliance\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Compliance\Enums\DpiaStatus;
use App\Domains\Compliance\Enums\DpiaTemplate;
use App\Domains\Compliance\Enums\RiskActionStatus;
use App\Domains\Compliance\Enums\RiskActionType;
use App\Domains\Compliance\Enums\RiskLevel;
use App\Domains\Compliance\Enums\RiskRegisterStatus;
use App\Domains\Compliance\Events\DpiaApproved;
use App\Domains\Compliance\Events\DpiaCreated;
use App\Domains\Compliance\Events\DpiaRejected;
use App\Domains\Compliance\Events\DpiaSubmitted;
use App\Domains\Compliance\Events\DpiaUpdated;
use App\Domains\Compliance\Events\RiskActionRecorded;
use App\Domains\Compliance\Events\RiskCreated;
use App\Domains\Compliance\Events\RiskUpdated;
use App\Domains\Compliance\Models\DpiaAssessment;
use App\Domains\Compliance\Models\RiskAction;
use App\Domains\Compliance\Models\RiskRegister;
use App\Domains\Compliance\Repositories\DpiaAssessmentRepository;
use App\Domains\Compliance\Repositories\RiskActionRepository;
use App\Domains\Compliance\Repositories\RiskRegisterRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DpiaService
{
    public function __construct(
        private readonly DpiaAssessmentRepository $dpiaAssessmentRepository,
        private readonly RiskRegisterRepository $riskRegisterRepository,
        private readonly RiskActionRepository $riskActionRepository,
        private readonly CompanyRepository $companyRepository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?string $companyIdentifier = null): array
    {
        $companyId = $this->resolveCompanyId($companyIdentifier);

        return [
            'dpia_statistics' => $this->dpiaAssessmentRepository->statistics($companyId),
            'risk_statistics' => $this->riskRegisterRepository->statistics($companyId),
            'recent_assessments' => $this->dpiaAssessmentRepository->recent($companyId),
            'pending_approval' => $this->dpiaAssessmentRepository->pendingApproval($companyId),
            'mitigation_queue' => $this->riskRegisterRepository->mitigationQueue($companyId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function riskMatrix(?string $companyIdentifier = null): array
    {
        return $this->riskRegisterRepository->riskMatrix($this->resolveCompanyId($companyIdentifier));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function templates(): array
    {
        return array_map(fn (DpiaTemplate $template) => [
            'code' => $template->value,
            'label' => $template->label(),
            'defaults' => $template->wizardDefaults(),
        ], DpiaTemplate::cases());
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listAssessments(array $filters = []): LengthAwarePaginator
    {
        return $this->dpiaAssessmentRepository->paginateFiltered(
            $this->normalizeCompanyFilter($filters)
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listRisks(array $filters = []): LengthAwarePaginator
    {
        $filters = $this->normalizeCompanyFilter($filters);

        if (! empty($filters['dpia_assessment_id']) && ! is_numeric($filters['dpia_assessment_id'])) {
            $dpia = $this->dpiaAssessmentRepository->findByIdentifierOrFail((string) $filters['dpia_assessment_id']);
            $filters['dpia_assessment_id'] = $dpia->id;
        }

        if (! empty($filters['owner_id']) && ! is_numeric($filters['owner_id'])) {
            $owner = $this->resolveUser($filters['owner_id']);
            $filters['owner_id'] = $owner?->id;
        }

        return $this->riskRegisterRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function mitigationTracker(array $filters = []): LengthAwarePaginator
    {
        $filters['mitigation_open'] = $filters['mitigation_open'] ?? '1';

        return $this->listRisks($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listActions(array $filters = []): LengthAwarePaginator
    {
        $filters = $this->normalizeCompanyFilter($filters);

        if (! empty($filters['risk_register_id']) && ! is_numeric($filters['risk_register_id'])) {
            $risk = $this->riskRegisterRepository->findByIdentifierOrFail((string) $filters['risk_register_id']);
            $filters['risk_register_id'] = $risk->id;
        }

        return $this->riskActionRepository->paginateFiltered($filters);
    }

    public function findAssessment(string $identifier, bool $withTrashed = false): DpiaAssessment
    {
        return $this->dpiaAssessmentRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function showAssessment(string $identifier): DpiaAssessment
    {
        return $this->findAssessment($identifier)->load([
            'company:id,uuid,company_name,status',
            'assignee:id,uuid,full_name,email',
            'reviewer:id,uuid,full_name,email',
            'submitter:id,uuid,full_name,email',
            'approver:id,uuid,full_name,email',
            'rejector:id,uuid,full_name,email',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
            'risks.owner:id,uuid,full_name,email',
            'risks.actions.performer:id,uuid,full_name,email',
        ]);
    }

    public function findRisk(string $identifier, bool $withTrashed = false): RiskRegister
    {
        return $this->riskRegisterRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function showRisk(string $identifier): RiskRegister
    {
        return $this->findRisk($identifier)->load([
            'company:id,uuid,company_name,status',
            'dpiaAssessment:id,uuid,assessment_number,title,status',
            'owner:id,uuid,full_name,email',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
            'actions.performer:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAssessment(array $data, User $actor): DpiaAssessment
    {
        return DB::transaction(function () use ($data, $actor): DpiaAssessment {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
            $assignee = $this->resolveUser($data['assigned_to'] ?? null);
            $template = DpiaTemplate::tryFrom((string) ($data['template_code'] ?? DpiaTemplate::Standard->value))
                ?? DpiaTemplate::Standard;

            $payload = $this->prepareAssessmentPayload($data);
            $payload['company_id'] = $company->id;
            $payload['assigned_to'] = $assignee?->id;
            $payload['assessment_number'] = $this->dpiaAssessmentRepository->generateAssessmentNumber();
            $payload['template_code'] = $template->value;
            $payload['status'] = $payload['status'] ?? DpiaStatus::Draft->value;
            $payload['wizard_step'] = $payload['wizard_step'] ?? 1;
            $payload['wizard_payload'] = $payload['wizard_payload'] ?? [
                'template' => $template->wizardDefaults(),
            ];
            $payload['review_due_at'] = $payload['review_due_at'] ?? now()->addYear()->toDateString();
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            $assessment = $this->dpiaAssessmentRepository->createAssessment($payload);
            event(new DpiaCreated($assessment, $actor));

            return $assessment;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAssessment(string $identifier, array $data, User $actor): DpiaAssessment
    {
        return DB::transaction(function () use ($identifier, $data, $actor): DpiaAssessment {
            $assessment = $this->dpiaAssessmentRepository->findByIdentifierOrFail($identifier);
            $previousStatus = $assessment->status;

            $payload = $this->prepareAssessmentPayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('assigned_to', $data)) {
                $payload['assigned_to'] = $this->resolveUser($data['assigned_to'])?->id;
            }

            if (array_key_exists('status', $payload)) {
                $target = DpiaStatus::tryFrom((string) $payload['status']);
                if ($target === null) {
                    throw new ApiException('Invalid DPIA status.', 422);
                }
                $this->assertDpiaTransition($previousStatus, $target);
            }

            $this->applyAssessmentRiskScores($payload);

            $updated = $this->dpiaAssessmentRepository->updateAssessment($assessment, $payload);
            event(new DpiaUpdated($updated, $actor));

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveWizardStep(string $identifier, array $data, User $actor): DpiaAssessment
    {
        return DB::transaction(function () use ($identifier, $data, $actor): DpiaAssessment {
            $assessment = $this->dpiaAssessmentRepository->findByIdentifierOrFail($identifier);

            if (in_array($assessment->status, [DpiaStatus::Approved, DpiaStatus::Archived], true)) {
                throw new ApiException('Approved or archived DPIAs cannot be edited in the wizard.', 422);
            }

            $wizardPayload = array_merge($assessment->wizard_payload ?? [], $data['wizard_payload'] ?? []);
            $step = (int) ($data['wizard_step'] ?? $assessment->wizard_step);

            $payload = [
                'wizard_step' => max(1, min($step, 10)),
                'wizard_payload' => $wizardPayload,
                'updated_by' => $actor->id,
            ];

            foreach ([
                'title', 'description', 'processing_purpose', 'data_categories', 'data_subjects',
                'processing_operations', 'necessity_proportionality', 'consultation_notes',
                'mitigation_summary', 'overall_risk_score', 'overall_risk_level',
                'residual_risk_score', 'residual_risk_level', 'review_due_at', 'next_review_at',
            ] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }

            if ($assessment->status === DpiaStatus::Draft) {
                $payload['status'] = DpiaStatus::InProgress->value;
            }

            $this->applyAssessmentRiskScores($payload);

            $updated = $this->dpiaAssessmentRepository->updateAssessment($assessment, $payload);
            event(new DpiaUpdated($updated, $actor));

            return $updated;
        });
    }

    public function submitForReview(string $identifier, User $actor, array $data = []): DpiaAssessment
    {
        return DB::transaction(function () use ($identifier, $actor, $data): DpiaAssessment {
            $assessment = $this->dpiaAssessmentRepository->findByIdentifierOrFail($identifier);
            $this->assertDpiaTransition($assessment->status, DpiaStatus::PendingReview);

            $updated = $this->dpiaAssessmentRepository->updateAssessment($assessment, [
                'status' => DpiaStatus::PendingReview->value,
                'submitted_at' => now(),
                'submitted_by' => $actor->id,
                'updated_by' => $actor->id,
                'consultation_notes' => $data['consultation_notes'] ?? $assessment->consultation_notes,
            ]);

            event(new DpiaSubmitted($updated, $actor));
            event(new DpiaUpdated($updated, $actor));

            return $updated;
        });
    }

    public function approve(string $identifier, User $actor, array $data = []): DpiaAssessment
    {
        return DB::transaction(function () use ($identifier, $actor, $data): DpiaAssessment {
            $assessment = $this->dpiaAssessmentRepository->findByIdentifierOrFail($identifier);
            $this->assertDpiaTransition($assessment->status, DpiaStatus::Approved);

            $nextReview = $data['next_review_at']
                ?? optional($assessment->review_due_at)?->toDateString()
                ?? now()->addYear()->toDateString();

            $updated = $this->dpiaAssessmentRepository->updateAssessment($assessment, [
                'status' => DpiaStatus::Approved->value,
                'approved_at' => now(),
                'approved_by' => $actor->id,
                'approval_notes' => $data['approval_notes'] ?? null,
                'reviewed_at' => now(),
                'reviewed_by' => $actor->id,
                'next_review_at' => $nextReview,
                'review_due_at' => $nextReview,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_notes' => null,
                'updated_by' => $actor->id,
            ]);

            event(new DpiaApproved($updated, $actor));

            return $updated;
        });
    }

    public function reject(string $identifier, User $actor, array $data = []): DpiaAssessment
    {
        return DB::transaction(function () use ($identifier, $actor, $data): DpiaAssessment {
            $assessment = $this->dpiaAssessmentRepository->findByIdentifierOrFail($identifier);
            $this->assertDpiaTransition($assessment->status, DpiaStatus::Rejected);

            $updated = $this->dpiaAssessmentRepository->updateAssessment($assessment, [
                'status' => DpiaStatus::Rejected->value,
                'rejected_at' => now(),
                'rejected_by' => $actor->id,
                'rejection_notes' => $data['rejection_notes'] ?? null,
                'updated_by' => $actor->id,
            ]);

            event(new DpiaRejected($updated, $actor));

            return $updated;
        });
    }

    public function deleteAssessment(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $assessment = $this->dpiaAssessmentRepository->findByIdentifierOrFail($identifier);
            $assessment->updated_by = $actor->id;
            $assessment->save();
            $assessment->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRisk(array $data, User $actor): RiskRegister
    {
        return DB::transaction(function () use ($data, $actor): RiskRegister {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
            $owner = $this->resolveUser($data['owner_id'] ?? null);
            $dpiaId = null;

            if (! empty($data['dpia_assessment_id'])) {
                $dpia = $this->dpiaAssessmentRepository->findByIdentifierOrFail((string) $data['dpia_assessment_id']);
                if ($dpia->company_id !== $company->id) {
                    throw new ApiException('DPIA does not belong to the selected company.', 422);
                }
                $dpiaId = $dpia->id;
            }

            $payload = $this->prepareRiskPayload($data);
            $payload['company_id'] = $company->id;
            $payload['dpia_assessment_id'] = $dpiaId;
            $payload['owner_id'] = $owner?->id;
            $payload['risk_number'] = $this->riskRegisterRepository->generateRiskNumber();
            $payload['status'] = $payload['status'] ?? RiskRegisterStatus::Identified->value;
            $payload['identified_at'] = $payload['identified_at'] ?? now();
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;
            $this->applyRiskScores($payload);

            $risk = $this->riskRegisterRepository->createRisk($payload);

            $this->riskActionRepository->recordTimeline(
                $risk,
                RiskActionType::Assessment->value,
                'Risk identified',
                'Risk added to register',
                null,
                $risk->status?->value,
                $actor->id,
                ['risk_score' => $risk->risk_score]
            );

            event(new RiskCreated($risk, $actor));

            return $risk;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRisk(string $identifier, array $data, User $actor): RiskRegister
    {
        return DB::transaction(function () use ($identifier, $data, $actor): RiskRegister {
            $risk = $this->riskRegisterRepository->findByIdentifierOrFail($identifier);
            $previousStatus = $risk->status;

            $payload = $this->prepareRiskPayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('owner_id', $data)) {
                $payload['owner_id'] = $this->resolveUser($data['owner_id'])?->id;
            }

            if (array_key_exists('status', $payload)) {
                $target = RiskRegisterStatus::tryFrom((string) $payload['status']);
                if ($target === null) {
                    throw new ApiException('Invalid risk status.', 422);
                }
                $this->assertRiskTransition($previousStatus, $target);
                $payload['closed_at'] = $target === RiskRegisterStatus::Closed
                    ? ($risk->closed_at ?? now())
                    : null;
            }

            $this->applyRiskScores($payload);

            $updated = $this->riskRegisterRepository->updateRisk($risk, $payload);

            if (isset($payload['status']) && $previousStatus?->value !== $updated->status?->value) {
                $this->riskActionRepository->recordTimeline(
                    $updated,
                    RiskActionType::StatusChange->value,
                    'Risk status changed',
                    null,
                    $previousStatus?->value,
                    $updated->status?->value,
                    $actor->id
                );
            }

            event(new RiskUpdated($updated, $actor));

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assessRisk(string $identifier, array $data, User $actor): RiskRegister
    {
        return DB::transaction(function () use ($identifier, $data, $actor): RiskRegister {
            $risk = $this->riskRegisterRepository->findByIdentifierOrFail($identifier);
            $previousStatus = $risk->status;

            $payload = [
                'likelihood' => (int) $data['likelihood'],
                'impact' => (int) $data['impact'],
                'updated_by' => $actor->id,
            ];

            if (array_key_exists('residual_likelihood', $data)) {
                $payload['residual_likelihood'] = (int) $data['residual_likelihood'];
            }
            if (array_key_exists('residual_impact', $data)) {
                $payload['residual_impact'] = (int) $data['residual_impact'];
            }
            if (array_key_exists('mitigation_plan', $data)) {
                $payload['mitigation_plan'] = $data['mitigation_plan'];
            }

            if ($previousStatus === RiskRegisterStatus::Identified
                && $previousStatus->canTransitionTo(RiskRegisterStatus::Assessing)) {
                $payload['status'] = RiskRegisterStatus::Assessing->value;
            }

            $this->applyRiskScores($payload);

            $updated = $this->riskRegisterRepository->updateRisk($risk, $payload);

            $this->riskActionRepository->recordTimeline(
                $updated,
                RiskActionType::Assessment->value,
                'Risk scored',
                $data['mitigation_plan'] ?? null,
                $previousStatus?->value,
                $updated->status?->value,
                $actor->id,
                [
                    'likelihood' => $updated->likelihood,
                    'impact' => $updated->impact,
                    'risk_score' => $updated->risk_score,
                ]
            );

            event(new RiskUpdated($updated, $actor));

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addRiskAction(string $identifier, array $data, User $actor): RiskAction
    {
        return DB::transaction(function () use ($identifier, $data, $actor): RiskAction {
            $risk = $this->riskRegisterRepository->findByIdentifierOrFail($identifier);
            $performer = $this->resolveUser($data['performed_by'] ?? $actor->uuid);

            $status = $data['status'] ?? RiskActionStatus::Planned->value;

            $action = $this->riskActionRepository->createForRisk($risk, [
                'action_type' => $data['action_type'] ?? RiskActionType::Mitigation->value,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => $status,
                'performed_by' => $performer?->id ?? $actor->id,
                'due_at' => $data['due_at'] ?? null,
                'completed_at' => $status === RiskActionStatus::Completed->value ? now() : null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            if ($risk->status === RiskRegisterStatus::Assessing
                || $risk->status === RiskRegisterStatus::Identified) {
                if ($risk->status->canTransitionTo(RiskRegisterStatus::Mitigating)) {
                    $this->riskRegisterRepository->updateRisk($risk, [
                        'status' => RiskRegisterStatus::Mitigating->value,
                        'updated_by' => $actor->id,
                    ]);
                }
            } else {
                $this->riskRegisterRepository->updateRisk($risk, ['updated_by' => $actor->id]);
            }

            event(new RiskActionRecorded($risk->fresh(), $action, $actor));

            return $action;
        });
    }

    public function completeRiskAction(string $riskIdentifier, string $actionIdentifier, User $actor): RiskAction
    {
        return DB::transaction(function () use ($riskIdentifier, $actionIdentifier, $actor): RiskAction {
            $risk = $this->riskRegisterRepository->findByIdentifierOrFail($riskIdentifier);
            $action = $this->riskActionRepository->findByIdentifierOrFail($actionIdentifier);

            if ($action->risk_register_id !== $risk->id) {
                throw new ApiException('Action does not belong to this risk.', 422);
            }

            $updated = $this->riskActionRepository->updateAction($action, [
                'status' => RiskActionStatus::Completed->value,
                'completed_at' => now(),
                'performed_by' => $actor->id,
            ]);

            $this->riskRegisterRepository->updateRisk($risk, ['updated_by' => $actor->id]);
            event(new RiskActionRecorded($risk->fresh(), $updated, $actor));

            return $updated;
        });
    }

    public function deleteRisk(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $risk = $this->riskRegisterRepository->findByIdentifierOrFail($identifier);
            $risk->updated_by = $actor->id;
            $risk->save();
            $risk->delete();
        });
    }

    private function assertDpiaTransition(?DpiaStatus $from, DpiaStatus $to): void
    {
        if ($from === null || ! $from->canTransitionTo($to)) {
            throw new ApiException(
                'Cannot transition DPIA from '.($from?->label() ?? 'unknown').' to '.$to->label().'.',
                422
            );
        }
    }

    private function assertRiskTransition(?RiskRegisterStatus $from, RiskRegisterStatus $to): void
    {
        if ($from === null || ! $from->canTransitionTo($to)) {
            throw new ApiException(
                'Cannot transition risk from '.($from?->label() ?? 'unknown').' to '.$to->label().'.',
                422
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareAssessmentPayload(array $data, bool $isUpdate = false): array
    {
        $keys = [
            'title', 'description', 'template_code', 'status', 'wizard_step', 'wizard_payload',
            'processing_purpose', 'data_categories', 'data_subjects', 'processing_operations',
            'necessity_proportionality', 'consultation_notes', 'overall_risk_score',
            'overall_risk_level', 'residual_risk_score', 'residual_risk_level',
            'mitigation_summary', 'review_due_at', 'next_review_at',
        ];

        $payload = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareRiskPayload(array $data, bool $isUpdate = false): array
    {
        $keys = [
            'title', 'description', 'category', 'status', 'likelihood', 'impact',
            'risk_score', 'risk_level', 'residual_likelihood', 'residual_impact',
            'residual_score', 'residual_level', 'mitigation_plan', 'review_due_at',
            'identified_at',
        ];

        $payload = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyRiskScores(array &$payload): void
    {
        if (isset($payload['likelihood'], $payload['impact'])) {
            $score = (int) $payload['likelihood'] * (int) $payload['impact'];
            $payload['risk_score'] = $score;
            $payload['risk_level'] = RiskLevel::fromRiskScore($score)->value;
        }

        if (isset($payload['residual_likelihood'], $payload['residual_impact'])) {
            $residual = (int) $payload['residual_likelihood'] * (int) $payload['residual_impact'];
            $payload['residual_score'] = $residual;
            $payload['residual_level'] = RiskLevel::fromRiskScore($residual)->value;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyAssessmentRiskScores(array &$payload): void
    {
        if (isset($payload['overall_risk_score']) && ! isset($payload['overall_risk_level'])) {
            $payload['overall_risk_level'] = RiskLevel::fromRiskScore((int) $payload['overall_risk_score'])->value;
        }

        if (isset($payload['residual_risk_score']) && ! isset($payload['residual_risk_level'])) {
            $payload['residual_risk_level'] = RiskLevel::fromRiskScore((int) $payload['residual_risk_score'])->value;
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function normalizeCompanyFilter(array $filters): array
    {
        $companyIdentifier = $filters['company'] ?? $filters['company_id'] ?? null;
        if (! empty($companyIdentifier) && ! is_numeric($companyIdentifier)) {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $companyIdentifier);
            $filters['company_id'] = $company->id;
        }

        $assignee = $filters['assigned_to'] ?? $filters['assignee'] ?? null;
        if (! empty($assignee) && ! is_numeric($assignee)) {
            $filters['assigned_to'] = $this->resolveUser($assignee)?->id;
        }

        return $filters;
    }

    private function resolveCompanyId(?string $companyIdentifier): ?int
    {
        if (blank($companyIdentifier)) {
            return null;
        }

        return $this->companyRepository->findByIdentifierOrFail($companyIdentifier)->id;
    }

    private function resolveUser(mixed $identifier): ?User
    {
        if (blank($identifier)) {
            return null;
        }

        if ($identifier instanceof User) {
            return $identifier;
        }

        if (is_numeric($identifier)) {
            return User::query()->find((int) $identifier);
        }

        return User::query()->where('uuid', (string) $identifier)->first();
    }
}

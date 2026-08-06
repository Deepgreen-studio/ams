<?php

namespace App\Domains\Compliance\Controllers;

use App\Domains\Compliance\Models\DpiaAssessment;
use App\Domains\Compliance\Models\RiskRegister;
use App\Domains\Compliance\Requests\ApproveDpiaRequest;
use App\Domains\Compliance\Requests\AssessRiskRegisterRequest;
use App\Domains\Compliance\Requests\RejectDpiaRequest;
use App\Domains\Compliance\Requests\SaveDpiaWizardRequest;
use App\Domains\Compliance\Requests\StoreDpiaAssessmentRequest;
use App\Domains\Compliance\Requests\StoreRiskActionRequest;
use App\Domains\Compliance\Requests\StoreRiskRegisterRequest;
use App\Domains\Compliance\Requests\SubmitDpiaRequest;
use App\Domains\Compliance\Requests\UpdateDpiaAssessmentRequest;
use App\Domains\Compliance\Requests\UpdateRiskRegisterRequest;
use App\Domains\Compliance\Resources\DpiaAssessmentCollection;
use App\Domains\Compliance\Resources\DpiaAssessmentResource;
use App\Domains\Compliance\Resources\RiskActionCollection;
use App\Domains\Compliance\Resources\RiskActionResource;
use App\Domains\Compliance\Resources\RiskRegisterCollection;
use App\Domains\Compliance\Resources\RiskRegisterResource;
use App\Domains\Compliance\Services\DpiaService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DpiaController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DpiaService $dpiaService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DpiaAssessment::class);

        $result = $this->dpiaService->dashboard($request->query('company'));

        return ApiResponse::success([
            'dpia_statistics' => $result['dpia_statistics'],
            'risk_statistics' => $result['risk_statistics'],
            'recent_assessments' => DpiaAssessmentResource::collection($result['recent_assessments'])->resolve(),
            'pending_approval' => DpiaAssessmentResource::collection($result['pending_approval'])->resolve(),
            'mitigation_queue' => RiskRegisterResource::collection($result['mitigation_queue'])->resolve(),
        ]);
    }

    public function riskMatrix(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DpiaAssessment::class);

        return ApiResponse::success(
            $this->dpiaService->riskMatrix($request->query('company'))
        );
    }

    public function templates(): JsonResponse
    {
        $this->authorize('viewAny', DpiaAssessment::class);

        return ApiResponse::success([
            'templates' => $this->dpiaService->templates(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DpiaAssessment::class);

        $assessments = $this->dpiaService->listAssessments($request->only([
            'search', 'status', 'template_code', 'overall_risk_level',
            'company', 'company_id', 'assigned_to', 'assignee',
            'review_overdue', 'sort_by', 'sort_dir', 'per_page', 'page', 'trashed',
        ]));

        return ApiResponse::success([
            'assessments' => (new DpiaAssessmentCollection($assessments))->resolve(),
        ]);
    }

    public function store(StoreDpiaAssessmentRequest $request): JsonResponse
    {
        $this->authorize('create', DpiaAssessment::class);

        /** @var User $actor */
        $actor = $request->user();
        $assessment = $this->dpiaService->createAssessment($request->validated(), $actor);

        return ApiResponse::success([
            'assessment' => new DpiaAssessmentResource($assessment),
        ], 'DPIA assessment created successfully.', 201);
    }

    public function show(string $assessment): JsonResponse
    {
        $model = $this->dpiaService->showAssessment($assessment);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'assessment' => new DpiaAssessmentResource($model),
        ]);
    }

    public function update(UpdateDpiaAssessmentRequest $request, string $assessment): JsonResponse
    {
        $existing = $this->dpiaService->findAssessment($assessment);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->dpiaService->updateAssessment($assessment, $request->validated(), $actor);

        return ApiResponse::success([
            'assessment' => new DpiaAssessmentResource($updated),
        ], 'DPIA assessment updated successfully.');
    }

    public function destroy(Request $request, string $assessment): JsonResponse
    {
        $existing = $this->dpiaService->findAssessment($assessment);
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->dpiaService->deleteAssessment($assessment, $actor);

        return ApiResponse::success(null, 'DPIA assessment deleted successfully.');
    }

    public function saveWizard(SaveDpiaWizardRequest $request, string $assessment): JsonResponse
    {
        $existing = $this->dpiaService->findAssessment($assessment);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->dpiaService->saveWizardStep($assessment, $request->validated(), $actor);

        return ApiResponse::success([
            'assessment' => new DpiaAssessmentResource($updated),
        ], 'DPIA wizard progress saved.');
    }

    public function submit(SubmitDpiaRequest $request, string $assessment): JsonResponse
    {
        $existing = $this->dpiaService->findAssessment($assessment);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->dpiaService->submitForReview($assessment, $actor, $request->validated());

        return ApiResponse::success([
            'assessment' => new DpiaAssessmentResource($updated),
        ], 'DPIA submitted for review.');
    }

    public function approve(ApproveDpiaRequest $request, string $assessment): JsonResponse
    {
        $existing = $this->dpiaService->findAssessment($assessment);
        $this->authorize('approve', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->dpiaService->approve($assessment, $actor, $request->validated());

        return ApiResponse::success([
            'assessment' => new DpiaAssessmentResource($updated),
        ], 'DPIA approved successfully.');
    }

    public function reject(RejectDpiaRequest $request, string $assessment): JsonResponse
    {
        $existing = $this->dpiaService->findAssessment($assessment);
        $this->authorize('approve', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->dpiaService->reject($assessment, $actor, $request->validated());

        return ApiResponse::success([
            'assessment' => new DpiaAssessmentResource($updated),
        ], 'DPIA rejected.');
    }

    public function risks(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RiskRegister::class);

        $risks = $this->dpiaService->listRisks($request->only([
            'search', 'status', 'category', 'risk_level', 'company', 'company_id',
            'dpia_assessment_id', 'owner_id', 'mitigation_open',
            'sort_by', 'sort_dir', 'per_page', 'page', 'trashed',
        ]));

        return ApiResponse::success([
            'risks' => (new RiskRegisterCollection($risks))->resolve(),
        ]);
    }

    public function mitigationTracker(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RiskRegister::class);

        $risks = $this->dpiaService->mitigationTracker($request->only([
            'search', 'status', 'category', 'risk_level', 'company', 'company_id',
            'owner_id', 'sort_by', 'sort_dir', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'risks' => (new RiskRegisterCollection($risks))->resolve(),
        ]);
    }

    public function storeRisk(StoreRiskRegisterRequest $request): JsonResponse
    {
        $this->authorize('create', RiskRegister::class);

        /** @var User $actor */
        $actor = $request->user();
        $risk = $this->dpiaService->createRisk($request->validated(), $actor);

        return ApiResponse::success([
            'risk' => new RiskRegisterResource($risk),
        ], 'Risk registered successfully.', 201);
    }

    public function showRisk(string $risk): JsonResponse
    {
        $model = $this->dpiaService->showRisk($risk);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'risk' => new RiskRegisterResource($model),
        ]);
    }

    public function updateRisk(UpdateRiskRegisterRequest $request, string $risk): JsonResponse
    {
        $existing = $this->dpiaService->findRisk($risk);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->dpiaService->updateRisk($risk, $request->validated(), $actor);

        return ApiResponse::success([
            'risk' => new RiskRegisterResource($updated),
        ], 'Risk updated successfully.');
    }

    public function destroyRisk(Request $request, string $risk): JsonResponse
    {
        $existing = $this->dpiaService->findRisk($risk);
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->dpiaService->deleteRisk($risk, $actor);

        return ApiResponse::success(null, 'Risk deleted successfully.');
    }

    public function assessRisk(AssessRiskRegisterRequest $request, string $risk): JsonResponse
    {
        $existing = $this->dpiaService->findRisk($risk);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->dpiaService->assessRisk($risk, $request->validated(), $actor);

        return ApiResponse::success([
            'risk' => new RiskRegisterResource($updated),
        ], 'Risk assessment scored successfully.');
    }

    public function storeRiskAction(StoreRiskActionRequest $request, string $risk): JsonResponse
    {
        $existing = $this->dpiaService->findRisk($risk);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $action = $this->dpiaService->addRiskAction($risk, $request->validated(), $actor);

        return ApiResponse::success([
            'action' => new RiskActionResource($action),
        ], 'Mitigation action recorded successfully.', 201);
    }

    public function completeRiskAction(Request $request, string $risk, string $action): JsonResponse
    {
        $existing = $this->dpiaService->findRisk($risk);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $completed = $this->dpiaService->completeRiskAction($risk, $action, $actor);

        return ApiResponse::success([
            'action' => new RiskActionResource($completed),
        ], 'Mitigation action completed.');
    }

    public function actions(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RiskRegister::class);

        $actions = $this->dpiaService->listActions($request->only([
            'search', 'status', 'action_type', 'company', 'company_id',
            'risk_register_id', 'open', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'actions' => (new RiskActionCollection($actions))->resolve(),
        ]);
    }
}

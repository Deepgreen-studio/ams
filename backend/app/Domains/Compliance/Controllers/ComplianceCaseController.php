<?php

namespace App\Domains\Compliance\Controllers;

use App\Domains\Compliance\Models\ComplianceCase;
use App\Domains\Compliance\Requests\StoreComplianceCaseRequest;
use App\Domains\Compliance\Requests\UpdateComplianceCaseRequest;
use App\Domains\Compliance\Resources\ComplianceCaseCollection;
use App\Domains\Compliance\Resources\ComplianceCaseResource;
use App\Domains\Compliance\Services\ComplianceCaseService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplianceCaseController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ComplianceCaseService $complianceCaseService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ComplianceCase::class);

        $result = $this->complianceCaseService->dashboard($request->query('company'));

        return ApiResponse::success([
            'statistics' => $result['statistics'],
            'recent_active' => ComplianceCaseResource::collection($result['recent_active'])->resolve(),
            'elevated' => ComplianceCaseResource::collection($result['elevated'])->resolve(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ComplianceCase::class);

        $cases = $this->complianceCaseService->list($request->only([
            'search',
            'status',
            'case_type',
            'priority',
            'company',
            'company_id',
            'assigned_to',
            'assignee',
            'due_before',
            'due_after',
            'overdue',
            'sort_by',
            'sort_dir',
            'per_page',
            'page',
            'trashed',
        ]));

        return ApiResponse::success([
            'cases' => (new ComplianceCaseCollection($cases))->resolve(),
        ]);
    }

    public function store(StoreComplianceCaseRequest $request): JsonResponse
    {
        $this->authorize('create', ComplianceCase::class);

        /** @var User $actor */
        $actor = $request->user();
        $case = $this->complianceCaseService->create($request->validated(), $actor);

        return ApiResponse::success([
            'case' => new ComplianceCaseResource($case),
        ], 'Compliance case created successfully.', 201);
    }

    public function show(string $case): JsonResponse
    {
        $model = $this->complianceCaseService->show($case);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'case' => new ComplianceCaseResource($model),
        ]);
    }

    public function update(UpdateComplianceCaseRequest $request, string $case): JsonResponse
    {
        $existing = $this->complianceCaseService->find($case);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->complianceCaseService->update($case, $request->validated(), $actor);

        return ApiResponse::success([
            'case' => new ComplianceCaseResource($updated),
        ], 'Compliance case updated successfully.');
    }

    public function destroy(Request $request, string $case): JsonResponse
    {
        $existing = $this->complianceCaseService->find($case);
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->complianceCaseService->delete($case, $actor);

        return ApiResponse::success(null, 'Compliance case deleted successfully.');
    }

    public function restore(Request $request, string $case): JsonResponse
    {
        $existing = $this->complianceCaseService->find($case, withTrashed: true);
        $this->authorize('restore', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->complianceCaseService->restore($case, $actor);

        return ApiResponse::success([
            'case' => new ComplianceCaseResource($restored),
        ], 'Compliance case restored successfully.');
    }
}

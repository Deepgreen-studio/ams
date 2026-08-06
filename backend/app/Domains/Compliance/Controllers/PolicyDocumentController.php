<?php

namespace App\Domains\Compliance\Controllers;

use App\Domains\Compliance\Models\PolicyDocument;
use App\Domains\Compliance\Requests\ComparePolicyVersionsRequest;
use App\Domains\Compliance\Requests\DecidePolicyApprovalRequest;
use App\Domains\Compliance\Requests\LinkPolicyCmsContentRequest;
use App\Domains\Compliance\Requests\PublishPolicyDocumentRequest;
use App\Domains\Compliance\Requests\RestorePolicyVersionRequest;
use App\Domains\Compliance\Requests\StorePolicyDocumentRequest;
use App\Domains\Compliance\Requests\SubmitPolicyDocumentRequest;
use App\Domains\Compliance\Requests\UpdatePolicyDocumentRequest;
use App\Domains\Compliance\Resources\PolicyApprovalCollection;
use App\Domains\Compliance\Resources\PolicyDocumentCollection;
use App\Domains\Compliance\Resources\PolicyDocumentResource;
use App\Domains\Compliance\Resources\PolicyVersionResource;
use App\Domains\Compliance\Services\PolicyDocumentService;
use App\Domains\Content\Resources\ContentVersionResource;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PolicyDocumentController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PolicyDocumentService $policyDocumentService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PolicyDocument::class);

        $result = $this->policyDocumentService->dashboard($request->query('company'));

        return ApiResponse::success([
            'statistics' => $result['statistics'],
            'recent' => PolicyDocumentResource::collection($result['recent'])->resolve(),
            'approval_queue' => \App\Domains\Compliance\Resources\PolicyApprovalResource::collection($result['approval_queue'])->resolve(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PolicyDocument::class);

        $policies = $this->policyDocumentService->list($request->only([
            'search', 'status', 'policy_type', 'company', 'company_id',
            'assigned_to', 'assignee', 'sort_by', 'sort_dir', 'per_page', 'page', 'trashed',
        ]));

        return ApiResponse::success([
            'policies' => (new PolicyDocumentCollection($policies))->resolve(),
        ]);
    }

    public function store(StorePolicyDocumentRequest $request): JsonResponse
    {
        $this->authorize('create', PolicyDocument::class);

        /** @var User $actor */
        $actor = $request->user();
        $policy = $this->policyDocumentService->create($request->validated(), $actor);

        return ApiResponse::success([
            'policy' => new PolicyDocumentResource($policy),
        ], 'Policy document created successfully.', 201);
    }

    public function show(string $policy): JsonResponse
    {
        $model = $this->policyDocumentService->show($policy);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'policy' => new PolicyDocumentResource($model),
        ]);
    }

    public function update(UpdatePolicyDocumentRequest $request, string $policy): JsonResponse
    {
        $existing = $this->policyDocumentService->find($policy);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->policyDocumentService->update($policy, $request->validated(), $actor);

        return ApiResponse::success([
            'policy' => new PolicyDocumentResource($updated),
        ], 'Policy document updated successfully.');
    }

    public function destroy(Request $request, string $policy): JsonResponse
    {
        $existing = $this->policyDocumentService->find($policy);
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->policyDocumentService->delete($policy, $actor);

        return ApiResponse::success(null, 'Policy document deleted successfully.');
    }

    public function versions(string $policy): JsonResponse
    {
        $existing = $this->policyDocumentService->find($policy);
        $this->authorize('view', $existing);

        $versions = $this->policyDocumentService->versions($policy);

        return ApiResponse::success([
            'versions' => PolicyVersionResource::collection($versions)->resolve(),
            'meta' => [
                'current_version' => $existing->current_version,
                'title' => $existing->title,
            ],
        ]);
    }

    public function showVersion(string $policy, string $version): JsonResponse
    {
        $existing = $this->policyDocumentService->find($policy);
        $this->authorize('view', $existing);

        $model = $this->policyDocumentService->showVersion($policy, $version);

        return ApiResponse::success([
            'version' => (new PolicyVersionResource($model))->withSnapshot(),
        ]);
    }

    public function compare(ComparePolicyVersionsRequest $request, string $policy): JsonResponse
    {
        $existing = $this->policyDocumentService->find($policy);
        $this->authorize('view', $existing);

        $result = $this->policyDocumentService->compare(
            $policy,
            (string) $request->validated('from'),
            (string) $request->validated('to')
        );

        return ApiResponse::success([
            'from' => (new PolicyVersionResource($result['from']))->withSnapshot(),
            'to' => (new PolicyVersionResource($result['to']))->withSnapshot(),
            'comparison' => $result['comparison'],
        ]);
    }

    public function restoreVersion(RestorePolicyVersionRequest $request, string $policy, string $version): JsonResponse
    {
        $existing = $this->policyDocumentService->find($policy);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->policyDocumentService->restoreVersion(
            $policy,
            $version,
            $actor,
            $request->validated()
        );

        return ApiResponse::success([
            'policy' => new PolicyDocumentResource($restored),
        ], 'Previous policy version restored as a new version.');
    }

    public function submit(SubmitPolicyDocumentRequest $request, string $policy): JsonResponse
    {
        $existing = $this->policyDocumentService->find($policy);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->policyDocumentService->submitForReview($policy, $actor, $request->validated());

        return ApiResponse::success([
            'policy' => new PolicyDocumentResource($updated),
        ], 'Policy submitted for review.');
    }

    public function publish(PublishPolicyDocumentRequest $request, string $policy): JsonResponse
    {
        $existing = $this->policyDocumentService->find($policy);
        $this->authorize('publish', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->policyDocumentService->publish($policy, $actor, $request->validated());

        return ApiResponse::success([
            'policy' => new PolicyDocumentResource($updated),
        ], 'Policy published successfully.');
    }

    public function approvalQueue(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PolicyDocument::class);

        $approvals = $this->policyDocumentService->approvalQueue($request->only([
            'status', 'company', 'company_id', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'approvals' => (new PolicyApprovalCollection($approvals))->resolve(),
        ]);
    }

    public function approve(DecidePolicyApprovalRequest $request, string $approval): JsonResponse
    {
        $this->authorize('approve', PolicyDocument::class);

        /** @var User $actor */
        $actor = $request->user();
        $policy = $this->policyDocumentService->approve($approval, $actor, $request->validated());

        return ApiResponse::success([
            'policy' => new PolicyDocumentResource($policy),
        ], 'Policy approved successfully.');
    }

    public function reject(DecidePolicyApprovalRequest $request, string $approval): JsonResponse
    {
        $this->authorize('approve', PolicyDocument::class);

        /** @var User $actor */
        $actor = $request->user();
        $policy = $this->policyDocumentService->reject($approval, $actor, $request->validated());

        return ApiResponse::success([
            'policy' => new PolicyDocumentResource($policy),
        ], 'Policy rejected and returned to draft.');
    }

    public function cmsVersions(string $policy): JsonResponse
    {
        $existing = $this->policyDocumentService->find($policy);
        $this->authorize('view', $existing);

        $result = $this->policyDocumentService->cmsVersionHistory($policy);

        return ApiResponse::success([
            'linked' => $result['linked'],
            'content' => $result['content'],
            'versions' => ContentVersionResource::collection($result['versions'])->resolve(),
        ]);
    }

    public function linkCms(LinkPolicyCmsContentRequest $request, string $policy): JsonResponse
    {
        $existing = $this->policyDocumentService->find($policy);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->policyDocumentService->linkCmsContent(
            $policy,
            (string) $request->validated('content_id'),
            $actor
        );

        return ApiResponse::success([
            'policy' => new PolicyDocumentResource($updated),
        ], 'Policy linked to CMS content successfully.');
    }
}

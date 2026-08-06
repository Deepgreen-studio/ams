<?php

namespace App\Domains\Workflows\Controllers;

use App\Domains\Workflows\Models\Workflow;
use App\Domains\Workflows\Requests\StartWorkflowInstanceRequest;
use App\Domains\Workflows\Requests\WorkflowDecisionRequest;
use App\Domains\Workflows\Resources\WorkflowInstanceResource;
use App\Domains\Workflows\Resources\WorkflowLogResource;
use App\Domains\Workflows\Services\WorkflowInstanceService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowInstanceController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly WorkflowInstanceService $instanceService,
    ) {}

    public function monitor(): JsonResponse
    {
        $this->authorize('viewAny', Workflow::class);
        $data = $this->instanceService->monitor();

        return ApiResponse::success([
            'statistics' => $data['statistics'],
            'recent' => WorkflowInstanceResource::collection($data['recent'])->resolve(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Workflow::class);
        $paginator = $this->instanceService->paginate($request->query());

        return ApiResponse::success([
            'instances' => [
                'items' => WorkflowInstanceResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function queue(Request $request): JsonResponse
    {
        $this->authorize('approve', Workflow::class);

        /** @var User $actor */
        $actor = $request->user();
        $paginator = $this->instanceService->approvalQueue($actor, $request->query());

        return ApiResponse::success([
            'queue' => [
                'items' => WorkflowInstanceResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Workflow::class);
        $paginator = $this->instanceService->paginateHistory($request->query());

        return ApiResponse::success([
            'logs' => [
                'items' => WorkflowLogResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function show(string $instance): JsonResponse
    {
        $model = $this->instanceService->find($instance);
        $this->authorize('viewAny', Workflow::class);

        return ApiResponse::success([
            'instance' => new WorkflowInstanceResource($model),
        ]);
    }

    public function start(StartWorkflowInstanceRequest $request, string $workflow): JsonResponse
    {
        $this->authorize('create', Workflow::class);

        /** @var User $actor */
        $actor = $request->user();
        $instance = $this->instanceService->start($workflow, $request->validated(), $actor);

        return ApiResponse::success([
            'instance' => new WorkflowInstanceResource($instance),
        ], 'Workflow instance started.', 201);
    }

    public function approve(WorkflowDecisionRequest $request, string $instance): JsonResponse
    {
        $this->authorize('approve', Workflow::class);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->instanceService->approve($instance, $actor, $request->validated('comment'));

        return ApiResponse::success([
            'instance' => new WorkflowInstanceResource($updated),
        ], 'Workflow approved.');
    }

    public function reject(WorkflowDecisionRequest $request, string $instance): JsonResponse
    {
        $this->authorize('approve', Workflow::class);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->instanceService->reject($instance, $actor, $request->validated('comment'));

        return ApiResponse::success([
            'instance' => new WorkflowInstanceResource($updated),
        ], 'Workflow rejected.');
    }

    public function cancel(WorkflowDecisionRequest $request, string $instance): JsonResponse
    {
        $this->authorize('manage', Workflow::class);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->instanceService->cancel($instance, $actor, $request->validated('comment'));

        return ApiResponse::success([
            'instance' => new WorkflowInstanceResource($updated),
        ], 'Workflow cancelled.');
    }
}

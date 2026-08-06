<?php

namespace App\Domains\Workflows\Controllers;

use App\Domains\Workflows\Models\Workflow;
use App\Domains\Workflows\Requests\IndexWorkflowRequest;
use App\Domains\Workflows\Requests\StoreWorkflowRequest;
use App\Domains\Workflows\Requests\UpdateWorkflowRequest;
use App\Domains\Workflows\Resources\WorkflowResource;
use App\Domains\Workflows\Services\WorkflowDefinitionService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly WorkflowDefinitionService $definitionService,
    ) {}

    public function dashboard(): JsonResponse
    {
        $this->authorize('viewAny', Workflow::class);

        return ApiResponse::success($this->definitionService->dashboard());
    }

    public function catalog(): JsonResponse
    {
        $this->authorize('viewAny', Workflow::class);

        return ApiResponse::success([
            'catalog' => $this->definitionService->catalog(),
        ]);
    }

    public function index(IndexWorkflowRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Workflow::class);
        $paginator = $this->definitionService->paginate($request->filters());

        return ApiResponse::success([
            'workflows' => [
                'items' => WorkflowResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
            'catalog' => $this->definitionService->catalog(),
        ]);
    }

    public function show(string $workflow): JsonResponse
    {
        $model = $this->definitionService->find($workflow);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'workflow' => new WorkflowResource($model),
            'catalog' => $this->definitionService->catalog(),
        ]);
    }

    public function store(StoreWorkflowRequest $request): JsonResponse
    {
        $this->authorize('create', Workflow::class);

        /** @var User $actor */
        $actor = $request->user();
        $workflow = $this->definitionService->create($request->validated(), $actor);

        return ApiResponse::success([
            'workflow' => new WorkflowResource($workflow),
        ], 'Workflow created.', 201);
    }

    public function update(UpdateWorkflowRequest $request, string $workflow): JsonResponse
    {
        $existing = $this->definitionService->find($workflow);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->definitionService->update($workflow, $request->validated(), $actor);

        return ApiResponse::success([
            'workflow' => new WorkflowResource($updated),
        ], 'Workflow updated.');
    }

    public function destroy(string $workflow): JsonResponse
    {
        $existing = $this->definitionService->find($workflow);
        $this->authorize('delete', $existing);
        $this->definitionService->delete($workflow, request()->user());

        return ApiResponse::success(null, 'Workflow deleted.');
    }

    public function toggle(Request $request, string $workflow): JsonResponse
    {
        $existing = $this->definitionService->find($workflow);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $enabled = $request->has('is_enabled') ? (bool) $request->boolean('is_enabled') : null;
        $updated = $this->definitionService->toggle($workflow, $actor, $enabled);

        return ApiResponse::success([
            'workflow' => new WorkflowResource($updated),
        ], $updated->is_enabled ? 'Workflow enabled.' : 'Workflow disabled.');
    }

    public function publish(string $workflow): JsonResponse
    {
        $existing = $this->definitionService->find($workflow);
        $this->authorize('update', $existing);
        $updated = $this->definitionService->publish($workflow, request()->user());

        return ApiResponse::success([
            'workflow' => new WorkflowResource($updated),
        ], 'Workflow published.');
    }

    public function archive(string $workflow): JsonResponse
    {
        $existing = $this->definitionService->find($workflow);
        $this->authorize('update', $existing);
        $updated = $this->definitionService->archive($workflow, request()->user());

        return ApiResponse::success([
            'workflow' => new WorkflowResource($updated),
        ], 'Workflow archived.');
    }
}

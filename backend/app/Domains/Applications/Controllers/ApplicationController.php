<?php

namespace App\Domains\Applications\Controllers;

use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Requests\StoreApplicationRequest;
use App\Domains\Applications\Requests\UpdateApplicationRequest;
use App\Domains\Applications\Resources\ApplicationCollection;
use App\Domains\Applications\Resources\ApplicationResource;
use App\Domains\Applications\Services\ApplicationService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ApplicationService $applicationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Application::class);

        $applications = $this->applicationService->list($request->only([
            'search',
            'status',
            'platform',
            'category',
            'visibility',
            'company',
            'company_id',
            'integration',
            'integration_id',
            'sort_by',
            'sort_dir',
            'per_page',
            'page',
            'trashed',
        ]));

        return ApiResponse::success([
            'applications' => (new ApplicationCollection($applications))->resolve(),
        ]);
    }

    public function store(StoreApplicationRequest $request): JsonResponse
    {
        $this->authorize('create', Application::class);

        /** @var User $actor */
        $actor = $request->user();
        $application = $this->applicationService->create($request->validated(), $actor);

        return ApiResponse::success([
            'application' => new ApplicationResource($application),
        ], 'Application created successfully.', 201);
    }

    public function show(string $application): JsonResponse
    {
        $model = $this->applicationService->show($application);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'application' => new ApplicationResource($model),
        ]);
    }

    public function update(UpdateApplicationRequest $request, string $application): JsonResponse
    {
        $existing = $this->applicationService->find($application);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->applicationService->update($application, $request->validated(), $actor);

        return ApiResponse::success([
            'application' => new ApplicationResource($updated),
        ], 'Application updated successfully.');
    }

    public function destroy(Request $request, string $application): JsonResponse
    {
        $existing = $this->applicationService->find($application);
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->applicationService->delete($application, $actor);

        return ApiResponse::success(null, 'Application deleted successfully.');
    }

    public function restore(Request $request, string $application): JsonResponse
    {
        $existing = $this->applicationService->find($application, withTrashed: true);
        $this->authorize('restore', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->applicationService->restore($application, $actor);

        return ApiResponse::success([
            'application' => new ApplicationResource($restored),
        ], 'Application restored successfully.');
    }
}

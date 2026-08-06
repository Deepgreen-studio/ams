<?php

namespace App\Domains\Applications\Controllers;

use App\Domains\Applications\Requests\StoreApplicationEnvironmentRequest;
use App\Domains\Applications\Requests\UpdateApplicationEnvironmentRequest;
use App\Domains\Applications\Resources\ApplicationEnvironmentCollection;
use App\Domains\Applications\Resources\ApplicationEnvironmentResource;
use App\Domains\Applications\Services\ApplicationEnvironmentService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationEnvironmentController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ApplicationEnvironmentService $environmentService
    ) {}

    public function dashboard(string $application): JsonResponse
    {
        $app = $this->environmentService->resolveApplication($application);
        $this->authorize('view', $app);

        $environments = $this->environmentService->dashboard($application);
        $current = $environments->firstWhere('is_current', true);

        $healthy = $environments->filter(
            fn ($env) => ($env->health_status?->value ?? $env->health_status) === 'healthy'
        )->count();
        $unhealthy = $environments->filter(
            fn ($env) => ($env->health_status?->value ?? $env->health_status) === 'unhealthy'
        )->count();

        return ApiResponse::success([
            'application' => [
                'id' => $app->id,
                'uuid' => $app->uuid,
                'name' => $app->name,
                'slug' => $app->slug,
            ],
            'current_environment' => $current ? new ApplicationEnvironmentResource($current) : null,
            'environments' => ApplicationEnvironmentResource::collection($environments),
            'summary' => [
                'total' => $environments->count(),
                'healthy' => $healthy,
                'unhealthy' => $unhealthy,
                'current' => $current?->uuid,
            ],
        ]);
    }

    public function index(Request $request, string $application): JsonResponse
    {
        $app = $this->environmentService->resolveApplication($application);
        $this->authorize('view', $app);

        $environments = $this->environmentService->list($application, $request->only([
            'search',
            'status',
            'type',
            'health_status',
            'sort_by',
            'sort_dir',
            'per_page',
            'page',
            'trashed',
        ]));

        return ApiResponse::success([
            'application' => [
                'uuid' => $app->uuid,
                'name' => $app->name,
            ],
            'environments' => (new ApplicationEnvironmentCollection($environments))->resolve(),
        ]);
    }

    public function store(StoreApplicationEnvironmentRequest $request, string $application): JsonResponse
    {
        $app = $this->environmentService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $environment = $this->environmentService->create($application, $request->validated(), $actor);

        return ApiResponse::success([
            'environment' => new ApplicationEnvironmentResource($environment),
        ], 'Environment created successfully.', 201);
    }

    public function show(string $application, string $environment): JsonResponse
    {
        $app = $this->environmentService->resolveApplication($application);
        $this->authorize('view', $app);

        $model = $this->environmentService->find($application, $environment);

        return ApiResponse::success([
            'environment' => new ApplicationEnvironmentResource($model),
        ]);
    }

    public function update(UpdateApplicationEnvironmentRequest $request, string $application, string $environment): JsonResponse
    {
        $app = $this->environmentService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->environmentService->update($application, $environment, $request->validated(), $actor);

        return ApiResponse::success([
            'environment' => new ApplicationEnvironmentResource($updated),
        ], 'Environment updated successfully.');
    }

    public function destroy(Request $request, string $application, string $environment): JsonResponse
    {
        $app = $this->environmentService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $this->environmentService->delete($application, $environment, $actor);

        return ApiResponse::success(null, 'Environment deleted successfully.');
    }

    public function switch(Request $request, string $application, string $environment): JsonResponse
    {
        $app = $this->environmentService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $switched = $this->environmentService->switch($application, $environment, $actor);

        return ApiResponse::success([
            'environment' => new ApplicationEnvironmentResource($switched),
        ], 'Environment switched successfully.');
    }

    public function healthCheck(Request $request, string $application, string $environment): JsonResponse
    {
        $app = $this->environmentService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $result = $this->environmentService->checkHealth($application, $environment, $actor);

        return ApiResponse::success([
            'environment' => new ApplicationEnvironmentResource($result['environment']),
            'check' => $result['check'],
        ], 'Health check completed.');
    }
}

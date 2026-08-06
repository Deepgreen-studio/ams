<?php

namespace App\Domains\Applications\Controllers;

use App\Domains\Applications\Requests\CompareApplicationVersionsRequest;
use App\Domains\Applications\Requests\StoreApplicationVersionRequest;
use App\Domains\Applications\Requests\UpdateApplicationVersionRequest;
use App\Domains\Applications\Resources\ApplicationVersionCollection;
use App\Domains\Applications\Resources\ApplicationVersionResource;
use App\Domains\Applications\Services\ApplicationVersionService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationVersionController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ApplicationVersionService $versionService
    ) {}

    public function index(Request $request, string $application): JsonResponse
    {
        $app = $this->versionService->resolveApplication($application);
        $this->authorize('view', $app);

        $versions = $this->versionService->list($application, $request->only([
            'search',
            'status',
            'sort_by',
            'sort_dir',
            'per_page',
            'page',
            'trashed',
        ]));

        return ApiResponse::success([
            'application' => [
                'id' => $app->id,
                'uuid' => $app->uuid,
                'name' => $app->name,
                'slug' => $app->slug,
                'current_version' => $app->current_version,
                'minimum_supported_version' => $app->minimum_supported_version,
            ],
            'versions' => (new ApplicationVersionCollection($versions))->resolve(),
        ]);
    }

    public function store(StoreApplicationVersionRequest $request, string $application): JsonResponse
    {
        $app = $this->versionService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $version = $this->versionService->create($application, $request->validated(), $actor);

        return ApiResponse::success([
            'version' => new ApplicationVersionResource($version),
        ], 'Application version created successfully.', 201);
    }

    public function show(string $application, string $version): JsonResponse
    {
        $app = $this->versionService->resolveApplication($application);
        $this->authorize('view', $app);

        $model = $this->versionService->find($application, $version);

        return ApiResponse::success([
            'version' => new ApplicationVersionResource($model),
        ]);
    }

    public function update(UpdateApplicationVersionRequest $request, string $application, string $version): JsonResponse
    {
        $app = $this->versionService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->versionService->update($application, $version, $request->validated(), $actor);

        return ApiResponse::success([
            'version' => new ApplicationVersionResource($updated),
        ], 'Application version updated successfully.');
    }

    public function destroy(Request $request, string $application, string $version): JsonResponse
    {
        $app = $this->versionService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $this->versionService->delete($application, $version, $actor);

        return ApiResponse::success(null, 'Application version deleted successfully.');
    }

    public function compare(CompareApplicationVersionsRequest $request, string $application): JsonResponse
    {
        $app = $this->versionService->resolveApplication($application);
        $this->authorize('view', $app);

        $result = $this->versionService->compare(
            $application,
            (string) $request->validated('from'),
            (string) $request->validated('to')
        );

        return ApiResponse::success([
            'from' => new ApplicationVersionResource($result['from']),
            'to' => new ApplicationVersionResource($result['to']),
            'comparison' => $result['comparison'],
        ]);
    }

    public function timeline(string $application): JsonResponse
    {
        $app = $this->versionService->resolveApplication($application);
        $this->authorize('view', $app);

        $versions = $this->versionService->timeline($application);

        return ApiResponse::success([
            'application' => [
                'uuid' => $app->uuid,
                'name' => $app->name,
                'current_version' => $app->current_version,
            ],
            'timeline' => ApplicationVersionResource::collection($versions),
        ]);
    }

    public function history(string $application): JsonResponse
    {
        $app = $this->versionService->resolveApplication($application);
        $this->authorize('view', $app);

        $versions = $this->versionService->history($application);

        return ApiResponse::success([
            'application' => [
                'uuid' => $app->uuid,
                'name' => $app->name,
            ],
            'history' => ApplicationVersionResource::collection($versions),
        ]);
    }
}

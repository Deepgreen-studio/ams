<?php

namespace App\Domains\Applications\Controllers;

use App\Domains\Applications\Requests\StoreApplicationConfigurationRequest;
use App\Domains\Applications\Requests\ToggleFeatureFlagRequest;
use App\Domains\Applications\Requests\UpdateApplicationConfigurationRequest;
use App\Domains\Applications\Requests\UpsertFeatureFlagRequest;
use App\Domains\Applications\Requests\ValidateApplicationConfigurationRequest;
use App\Domains\Applications\Resources\ApplicationConfigurationCollection;
use App\Domains\Applications\Resources\ApplicationConfigurationHistoryResource;
use App\Domains\Applications\Resources\ApplicationConfigurationResource;
use App\Domains\Applications\Services\ApplicationConfigurationService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationConfigurationController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ApplicationConfigurationService $configurationService
    ) {}

    public function catalog(string $application): JsonResponse
    {
        $app = $this->configurationService->resolveApplication($application);
        $this->authorize('view', $app);

        return ApiResponse::success([
            'catalog' => $this->configurationService->catalog(),
        ]);
    }

    public function manager(Request $request, string $application): JsonResponse
    {
        $app = $this->configurationService->resolveApplication($application);
        $this->authorize('view', $app);

        $configurations = $this->configurationService->manager(
            $application,
            $request->query('environment')
        );

        return ApiResponse::success([
            'application' => [
                'uuid' => $app->uuid,
                'name' => $app->name,
            ],
            'configurations' => ApplicationConfigurationResource::collection($configurations),
            'catalog' => $this->configurationService->catalog(),
        ]);
    }

    public function index(Request $request, string $application): JsonResponse
    {
        $app = $this->configurationService->resolveApplication($application);
        $this->authorize('view', $app);

        $configurations = $this->configurationService->list($application, $request->only([
            'search',
            'type',
            'status',
            'is_active',
            'environment',
            'environment_id',
            'sort_by',
            'sort_dir',
            'per_page',
            'page',
        ]));

        return ApiResponse::success([
            'configurations' => (new ApplicationConfigurationCollection($configurations))->resolve(),
        ]);
    }

    public function store(StoreApplicationConfigurationRequest $request, string $application): JsonResponse
    {
        $app = $this->configurationService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $configuration = $this->configurationService->create($application, $request->validated(), $actor);

        return ApiResponse::success([
            'configuration' => new ApplicationConfigurationResource($configuration),
        ], 'Configuration created successfully.', 201);
    }

    public function show(string $application, string $configuration): JsonResponse
    {
        $app = $this->configurationService->resolveApplication($application);
        $this->authorize('view', $app);

        $model = $this->configurationService->find($application, $configuration);

        return ApiResponse::success([
            'configuration' => new ApplicationConfigurationResource($model),
        ]);
    }

    public function update(UpdateApplicationConfigurationRequest $request, string $application, string $configuration): JsonResponse
    {
        $app = $this->configurationService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->configurationService->update($application, $configuration, $request->validated(), $actor);

        return ApiResponse::success([
            'configuration' => new ApplicationConfigurationResource($updated),
        ], 'Configuration updated successfully.');
    }

    public function destroy(Request $request, string $application, string $configuration): JsonResponse
    {
        $app = $this->configurationService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $this->configurationService->delete($application, $configuration, $actor);

        return ApiResponse::success(null, 'Configuration deleted successfully.');
    }

    public function history(string $application, string $configuration): JsonResponse
    {
        $app = $this->configurationService->resolveApplication($application);
        $this->authorize('view', $app);

        $history = $this->configurationService->history($application, $configuration);

        return ApiResponse::success([
            'history' => ApplicationConfigurationHistoryResource::collection($history),
        ]);
    }

    public function restoreHistory(Request $request, string $application, string $configuration, string $history): JsonResponse
    {
        $app = $this->configurationService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->configurationService->restoreHistory($application, $configuration, $history, $actor);

        return ApiResponse::success([
            'configuration' => new ApplicationConfigurationResource($restored),
        ], 'Configuration restored from history.');
    }

    public function validatePayload(ValidateApplicationConfigurationRequest $request, string $application): JsonResponse
    {
        $app = $this->configurationService->resolveApplication($application);
        $this->authorize('view', $app);

        $result = $this->configurationService->validatePayload(
            (string) $request->validated('type'),
            $request->validated('payload')
        );

        return ApiResponse::success($result, $result['valid'] ? 'Configuration is valid.' : 'Configuration validation failed.');
    }

    public function upsertFeatureFlag(
        UpsertFeatureFlagRequest $request,
        string $application,
        string $configuration
    ): JsonResponse {
        $app = $this->configurationService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->configurationService->upsertFeatureFlag(
            $application,
            $configuration,
            $request->validated(),
            $actor
        );

        return ApiResponse::success([
            'configuration' => new ApplicationConfigurationResource($updated),
        ], 'Feature flag saved successfully.');
    }

    public function toggleFeatureFlag(
        ToggleFeatureFlagRequest $request,
        string $application,
        string $configuration,
        string $flag
    ): JsonResponse {
        $app = $this->configurationService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->configurationService->toggleFeatureFlag(
            $application,
            $configuration,
            $flag,
            (bool) $request->validated('enabled'),
            $actor
        );

        return ApiResponse::success([
            'configuration' => new ApplicationConfigurationResource($updated),
        ], 'Feature flag toggled successfully.');
    }
}

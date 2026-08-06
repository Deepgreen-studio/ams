<?php

namespace App\Domains\Integrations\Controllers;

use App\Domains\Integrations\Models\Integration;
use App\Domains\Integrations\Requests\StoreIntegrationRequest;
use App\Domains\Integrations\Requests\UpdateIntegrationRequest;
use App\Domains\Integrations\Resources\IntegrationCollection;
use App\Domains\Integrations\Resources\IntegrationResource;
use App\Domains\Integrations\Services\IntegrationService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly IntegrationService $integrationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Integration::class);

        $integrations = $this->integrationService->list($request->only([
            'search',
            'status',
            'type',
            'authentication_type',
            'health_status',
            'company',
            'company_id',
            'sort_by',
            'sort_dir',
            'per_page',
            'page',
            'trashed',
        ]));

        return ApiResponse::success([
            'integrations' => (new IntegrationCollection($integrations))->resolve(),
        ]);
    }

    public function store(StoreIntegrationRequest $request): JsonResponse
    {
        $this->authorize('create', Integration::class);

        /** @var User $actor */
        $actor = $request->user();
        $integration = $this->integrationService->create($request->validated(), $actor);

        return ApiResponse::success([
            'integration' => new IntegrationResource($integration),
        ], 'Integration created successfully.', 201);
    }

    public function show(string $integration): JsonResponse
    {
        $model = $this->integrationService->show($integration);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'integration' => new IntegrationResource($model),
        ]);
    }

    public function update(UpdateIntegrationRequest $request, string $integration): JsonResponse
    {
        $existing = $this->integrationService->find($integration);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->integrationService->update($integration, $request->validated(), $actor);

        return ApiResponse::success([
            'integration' => new IntegrationResource($updated),
        ], 'Integration updated successfully.');
    }

    public function destroy(Request $request, string $integration): JsonResponse
    {
        $existing = $this->integrationService->find($integration);
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->integrationService->delete($integration, $actor);

        return ApiResponse::success(null, 'Integration deleted successfully.');
    }

    public function restore(Request $request, string $integration): JsonResponse
    {
        $existing = $this->integrationService->find($integration, withTrashed: true);
        $this->authorize('restore', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->integrationService->restore($integration, $actor);

        return ApiResponse::success([
            'integration' => new IntegrationResource($restored),
        ], 'Integration restored successfully.');
    }
}

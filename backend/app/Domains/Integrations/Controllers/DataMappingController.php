<?php

namespace App\Domains\Integrations\Controllers;

use App\Domains\Integrations\Models\DataMapping;
use App\Domains\Integrations\Requests\PreviewDataMappingRequest;
use App\Domains\Integrations\Requests\StoreDataMappingRequest;
use App\Domains\Integrations\Requests\UpdateDataMappingRequest;
use App\Domains\Integrations\Resources\DataMappingCollection;
use App\Domains\Integrations\Resources\DataMappingResource;
use App\Domains\Integrations\Services\DataMappingService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataMappingController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DataMappingService $mappingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DataMapping::class);
        $mappings = $this->mappingService->list($request->only([
            'search', 'direction', 'status', 'is_active', 'company', 'company_id',
            'integration', 'integration_id', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'mappings' => (new DataMappingCollection($mappings))->resolve(),
        ]);
    }

    public function catalogs(): JsonResponse
    {
        $this->authorize('viewAny', DataMapping::class);

        return ApiResponse::success($this->mappingService->catalogs());
    }

    public function store(StoreDataMappingRequest $request): JsonResponse
    {
        $this->authorize('create', DataMapping::class);
        /** @var User $actor */
        $actor = $request->user();
        $mapping = $this->mappingService->create($request->validated(), $actor);

        return ApiResponse::success([
            'mapping' => new DataMappingResource($mapping),
        ], 'Data mapping created successfully.', 201);
    }

    public function show(string $mapping): JsonResponse
    {
        $model = $this->mappingService->show($mapping);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'mapping' => new DataMappingResource($model),
        ]);
    }

    public function update(UpdateDataMappingRequest $request, string $mapping): JsonResponse
    {
        $model = $this->mappingService->find($mapping);
        $this->authorize('update', $model);
        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->mappingService->update($mapping, $request->validated(), $actor);

        return ApiResponse::success([
            'mapping' => new DataMappingResource($updated),
        ], 'Data mapping updated successfully.');
    }

    public function destroy(Request $request, string $mapping): JsonResponse
    {
        $model = $this->mappingService->find($mapping);
        $this->authorize('delete', $model);
        /** @var User $actor */
        $actor = $request->user();
        $this->mappingService->delete($mapping, $actor);

        return ApiResponse::success(null, 'Data mapping deleted successfully.');
    }

    public function preview(PreviewDataMappingRequest $request, string $mapping): JsonResponse
    {
        $model = $this->mappingService->find($mapping);
        $this->authorize('preview', $model);
        $result = $this->mappingService->preview($mapping, $request->validated());

        return ApiResponse::success([
            'mapping' => new DataMappingResource($result['mapping']),
            'source' => $result['source'],
            'direction' => $result['direction'],
            'result' => $result['result'],
        ]);
    }

    public function validateMapping(PreviewDataMappingRequest $request, string $mapping): JsonResponse
    {
        $model = $this->mappingService->find($mapping);
        $this->authorize('preview', $model);
        $result = $this->mappingService->validatePayload($mapping, $request->validated());

        return ApiResponse::success($result, $result['valid'] ? 'Mapping validation passed.' : 'Mapping validation failed.');
    }
}

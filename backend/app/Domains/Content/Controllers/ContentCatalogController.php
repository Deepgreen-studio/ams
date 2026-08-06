<?php

namespace App\Domains\Content\Controllers;

use App\Domains\Content\Models\Content;
use App\Domains\Content\Requests\StoreContentTypeRequest;
use App\Domains\Content\Requests\UpdateContentTypeRequest;
use App\Domains\Content\Resources\ContentStatusResource;
use App\Domains\Content\Resources\ContentTypeResource;
use App\Domains\Content\Services\ContentCatalogService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class ContentCatalogController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ContentCatalogService $catalogService
    ) {}

    public function types(): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        return ApiResponse::success([
            'types' => ContentTypeResource::collection($this->catalogService->listTypes()),
        ]);
    }

    public function storeType(StoreContentTypeRequest $request): JsonResponse
    {
        $this->authorize('create', Content::class);

        /** @var User $actor */
        $actor = $request->user();
        $type = $this->catalogService->createType($request->validated(), $actor);

        return ApiResponse::success([
            'type' => new ContentTypeResource($type),
        ], 'Content type created successfully.', 201);
    }

    public function updateType(UpdateContentTypeRequest $request, string $type): JsonResponse
    {
        $this->authorize('create', Content::class);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->catalogService->updateType($type, $request->validated(), $actor);

        return ApiResponse::success([
            'type' => new ContentTypeResource($updated),
        ], 'Content type updated successfully.');
    }

    public function statuses(): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        return ApiResponse::success([
            'statuses' => ContentStatusResource::collection($this->catalogService->listStatuses()),
        ]);
    }
}

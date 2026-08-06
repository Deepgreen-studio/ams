<?php

namespace App\Domains\Content\Controllers;

use App\Domains\Content\Models\Content;
use App\Domains\Content\Requests\StoreCmsApiKeyRequest;
use App\Domains\Content\Resources\CmsApiKeyResource;
use App\Domains\Content\Services\CmsApiKeyService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsApiKeyController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CmsApiKeyService $cmsApiKeyService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        $keys = $this->cmsApiKeyService->list($request->only(['search', 'per_page', 'page']));

        return ApiResponse::success([
            'api_keys' => [
                'items' => CmsApiKeyResource::collection($keys->items())->resolve(),
                'meta' => [
                    'current_page' => $keys->currentPage(),
                    'per_page' => $keys->perPage(),
                    'total' => $keys->total(),
                    'last_page' => $keys->lastPage(),
                ],
            ],
        ]);
    }

    public function store(StoreCmsApiKeyRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $result = $this->cmsApiKeyService->create($request->validated(), $actor);

        return ApiResponse::success([
            'api_key' => new CmsApiKeyResource($result['key']),
            'plain_text' => $result['plain_text'],
        ], 'CMS API key created. Store the plain text key now; it will not be shown again.', 201);
    }

    public function destroy(string $apiKey): JsonResponse
    {
        $this->cmsApiKeyService->revoke($apiKey);

        return ApiResponse::success(null, 'CMS API key revoked.');
    }
}

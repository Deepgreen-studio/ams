<?php

namespace App\Domains\Support\Controllers;

use App\Domains\Support\Requests\StoreSupportCannedResponseRequest;
use App\Domains\Support\Requests\UpdateSupportCannedResponseRequest;
use App\Domains\Support\Resources\SupportCannedResponseResource;
use App\Domains\Support\Services\SupportCannedResponseService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportCannedResponseController
{
    public function __construct(
        private readonly SupportCannedResponseService $cannedResponseService,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $data = $this->cannedResponseService->dashboard($actor);

        return ApiResponse::success([
            'statistics' => $data['statistics'],
            'recent' => SupportCannedResponseResource::collection($data['recent'])->resolve(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $paginator = $this->cannedResponseService->list($actor, $request->query());

        return ApiResponse::success([
            'responses' => [
                'items' => SupportCannedResponseResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function show(Request $request, string $response): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $model = $this->cannedResponseService->findAccessible($response, $actor);

        return ApiResponse::success([
            'response' => new SupportCannedResponseResource($model),
        ]);
    }

    public function store(StoreSupportCannedResponseRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $model = $this->cannedResponseService->create($request->validated(), $actor);

        return ApiResponse::success([
            'response' => new SupportCannedResponseResource($model),
        ], 'Canned response created successfully.', 201);
    }

    public function update(UpdateSupportCannedResponseRequest $request, string $response): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $model = $this->cannedResponseService->update($response, $request->validated(), $actor);

        return ApiResponse::success([
            'response' => new SupportCannedResponseResource($model),
        ], 'Canned response updated successfully.');
    }

    public function destroy(Request $request, string $response): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->cannedResponseService->delete($response, $actor);

        return ApiResponse::success(null, 'Canned response deleted successfully.');
    }

    public function use(Request $request, string $response): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $model = $this->cannedResponseService->markUsed($response, $actor);

        return ApiResponse::success([
            'response' => new SupportCannedResponseResource($model),
        ], 'Canned response applied.');
    }
}

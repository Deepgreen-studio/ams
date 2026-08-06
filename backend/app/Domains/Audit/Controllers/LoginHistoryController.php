<?php

namespace App\Domains\Audit\Controllers;

use App\Domains\Audit\Requests\FilterLoginHistoryRequest;
use App\Domains\Audit\Resources\LoginHistoryResource;
use App\Domains\Audit\Services\LoginHistoryService;
use App\Domains\Users\Models\UserLoginHistory;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class LoginHistoryController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly LoginHistoryService $loginHistoryService
    ) {}

    public function index(FilterLoginHistoryRequest $request): JsonResponse
    {
        $this->authorize('viewAny', UserLoginHistory::class);

        $logs = $this->loginHistoryService->list($request->validated());

        return ApiResponse::success([
            'login_history' => [
                'items' => LoginHistoryResource::collection($logs->items()),
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'from' => $logs->firstItem(),
                    'to' => $logs->lastItem(),
                ],
            ],
        ]);
    }
}

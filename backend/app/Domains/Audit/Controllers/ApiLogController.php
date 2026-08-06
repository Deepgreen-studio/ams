<?php

namespace App\Domains\Audit\Controllers;

use App\Domains\Audit\Models\ApiLog;
use App\Domains\Audit\Resources\ApiLogResource;
use App\Domains\Audit\Services\ApiLogService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiLogController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ApiLogService $apiLogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewApiLogs', ApiLog::class);

        $logs = $this->apiLogService->list($request->only([
            'search', 'method', 'response_code', 'user_id', 'date_from', 'date_to', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'api_logs' => [
                'items' => ApiLogResource::collection($logs->items()),
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

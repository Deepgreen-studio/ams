<?php

namespace App\Domains\Audit\Controllers;

use App\Domains\Audit\Models\ErrorLog;
use App\Domains\Audit\Resources\ErrorLogResource;
use App\Domains\Audit\Services\ErrorLogService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ErrorLogController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ErrorLogService $errorLogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewErrors', ErrorLog::class);

        $logs = $this->errorLogService->list($request->only([
            'search', 'exception', 'user_id', 'date_from', 'date_to', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'error_logs' => [
                'items' => ErrorLogResource::collection($logs->items()),
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

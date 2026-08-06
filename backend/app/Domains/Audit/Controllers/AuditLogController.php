<?php

namespace App\Domains\Audit\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Requests\FilterAuditLogRequest;
use App\Domains\Audit\Resources\AuditLogResource;
use App\Domains\Audit\Services\AuditTrailService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class AuditLogController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AuditTrailService $auditTrailService
    ) {}

    public function index(FilterAuditLogRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $logs = $this->auditTrailService->list($request->validated());

        return ApiResponse::success([
            'audit_logs' => [
                'items' => AuditLogResource::collection($logs->items()),
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

    public function show(string $auditLog): JsonResponse
    {
        $log = $this->auditTrailService->show($auditLog);
        $this->authorize('view', $log);

        return ApiResponse::success([
            'audit_log' => new AuditLogResource($log),
        ]);
    }
}

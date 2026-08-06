<?php

namespace App\Domains\Audit\Controllers;

use App\Domains\Audit\Models\ActivityLog;
use App\Domains\Audit\Requests\ExportLogRequest;
use App\Domains\Audit\Requests\FilterActivityLogRequest;
use App\Domains\Audit\Resources\ActivityLogResource;
use App\Domains\Audit\Services\ActivityLogService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {}

    public function index(FilterActivityLogRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ActivityLog::class);

        $logs = $this->activityLogService->list($request->validated());

        return ApiResponse::success([
            'activity_logs' => [
                'items' => ActivityLogResource::collection($logs->items()),
                'meta' => $this->meta($logs),
            ],
        ]);
    }

    public function show(string $activityLog): JsonResponse
    {
        $log = $this->activityLogService->show($activityLog);
        $this->authorize('view', $log);

        return ApiResponse::success([
            'activity_log' => new ActivityLogResource($log),
        ]);
    }

    public function export(ExportLogRequest $request): StreamedResponse|JsonResponse
    {
        $this->authorize('export', ActivityLog::class);

        $format = $request->validated('format', 'csv');
        if ($format !== 'csv') {
            return ApiResponse::error('Only CSV export is currently available. Excel/PDF are architecture-ready.', 422);
        }

        return $this->activityLogService->exportCsv($request->validated());
    }

    /**
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, mixed>  $paginator
     * @return array<string, mixed>
     */
    protected function meta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}

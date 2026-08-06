<?php

namespace App\Domains\Audit\Controllers;

use App\Domains\Audit\Models\SystemEvent;
use App\Domains\Audit\Resources\SystemEventResource;
use App\Domains\Audit\Services\SystemEventService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemEventController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SystemEventService $systemEventService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SystemEvent::class);

        $events = $this->systemEventService->list($request->only([
            'search', 'module', 'event', 'level', 'date_from', 'date_to', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'system_events' => [
                'items' => SystemEventResource::collection($events->items()),
                'meta' => [
                    'current_page' => $events->currentPage(),
                    'last_page' => $events->lastPage(),
                    'per_page' => $events->perPage(),
                    'total' => $events->total(),
                    'from' => $events->firstItem(),
                    'to' => $events->lastItem(),
                ],
            ],
        ]);
    }
}

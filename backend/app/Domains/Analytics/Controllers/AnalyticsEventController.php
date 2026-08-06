<?php

namespace App\Domains\Analytics\Controllers;

use App\Domains\Analytics\Models\AnalyticsEvent;
use App\Domains\Analytics\Models\AnalyticsSubject;
use App\Domains\Analytics\Requests\IndexAnalyticsEventRequest;
use App\Domains\Analytics\Requests\StoreAnalyticsEventRequest;
use App\Domains\Analytics\Resources\AnalyticsEventCollection;
use App\Domains\Analytics\Resources\AnalyticsEventResource;
use App\Domains\Analytics\Services\AnalyticsEventService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class AnalyticsEventController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AnalyticsEventService $eventService,
    ) {}

    public function index(IndexAnalyticsEventRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        $events = $this->eventService->paginate($request->filters());

        return ApiResponse::success([
            'events' => (new AnalyticsEventCollection($events))->resolve(),
        ]);
    }

    public function summary(IndexAnalyticsEventRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        return ApiResponse::success(
            $this->eventService->summary($request->filters())
        );
    }

    public function store(StoreAnalyticsEventRequest $request): JsonResponse
    {
        $this->authorize('create', AnalyticsEvent::class);

        $event = $this->eventService->record($request->validated(), $request->user());

        return ApiResponse::success([
            'event' => new AnalyticsEventResource($event),
        ], 'Analytics event recorded.', 201);
    }

    public function show(string $event): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        $model = $this->eventService->find($event);

        return ApiResponse::success([
            'event' => new AnalyticsEventResource($model),
        ]);
    }
}

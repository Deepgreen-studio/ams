<?php

namespace App\Domains\Analytics\Controllers;

use App\Domains\Analytics\Models\AnalyticsSubject;
use App\Domains\Analytics\Repositories\AnalyticsWidgetRepository;
use App\Domains\Analytics\Requests\StoreAnalyticsWidgetRequest;
use App\Domains\Analytics\Requests\UpdateAnalyticsWidgetRequest;
use App\Domains\Analytics\Resources\AnalyticsWidgetResource;
use App\Domains\Analytics\Services\AnalyticsDashboardService;
use App\Domains\Analytics\Services\AnalyticsWidgetLibraryService;
use App\Domains\Analytics\Services\AnalyticsWidgetService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class AnalyticsWidgetController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AnalyticsDashboardService $dashboardService,
        private readonly AnalyticsWidgetService $widgetService,
        private readonly AnalyticsWidgetRepository $widgetRepository,
        private readonly AnalyticsWidgetLibraryService $libraryService,
    ) {}

    public function library(): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        return ApiResponse::success($this->libraryService->catalog());
    }

    public function store(StoreAnalyticsWidgetRequest $request, string $dashboard): JsonResponse
    {
        $dashboardModel = $this->dashboardService->find($dashboard);
        $this->authorize('update', $dashboardModel);

        $widget = $this->widgetService->create($dashboardModel, $request->validated(), $request->user());

        return ApiResponse::success([
            'widget' => new AnalyticsWidgetResource($widget),
        ], 'Analytics widget created.', 201);
    }

    public function update(UpdateAnalyticsWidgetRequest $request, string $widget): JsonResponse
    {
        $model = $this->widgetRepository->findByUuidOrFail($widget);
        $this->authorize('update', $model);

        $updated = $this->widgetService->update($model, $request->validated(), $request->user());

        return ApiResponse::success([
            'widget' => new AnalyticsWidgetResource($updated),
        ], 'Analytics widget updated.');
    }

    public function destroy(string $widget): JsonResponse
    {
        $model = $this->widgetRepository->findByUuidOrFail($widget);
        $this->authorize('delete', $model);

        $this->widgetService->delete($model, request()->user());

        return ApiResponse::success(null, 'Analytics widget deleted.');
    }
}

<?php

namespace App\Domains\Analytics\Controllers;

use App\Domains\Analytics\Models\AnalyticsSubject;
use App\Domains\Analytics\Requests\FilterEnterpriseAnalyticsRequest;
use App\Domains\Analytics\Resources\AnalyticsDashboardCollection;
use App\Domains\Analytics\Services\AnalyticsEventService;
use App\Domains\Analytics\Services\AnalyticsOverviewService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class AnalyticsOverviewController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AnalyticsOverviewService $overviewService,
        private readonly AnalyticsEventService $eventService,
    ) {}

    public function overview(FilterEnterpriseAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        $data = $this->overviewService->overview($request->validated());

        if (isset($data['recent_dashboards'])) {
            $data['recent_dashboards'] = (new AnalyticsDashboardCollection(
                $data['recent_dashboards']
            ))->resolve();
        }

        if (isset($data['saved_views'])) {
            $data['saved_views'] = (new AnalyticsDashboardCollection(
                $data['saved_views']
            ))->resolve();
        }

        return ApiResponse::success($data);
    }

    public function categories(): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        return ApiResponse::success([
            'categories' => $this->eventService->categories(),
        ]);
    }
}

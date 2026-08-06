<?php

namespace App\Domains\Analytics\Controllers;

use App\Domains\Analytics\Enums\ExecutiveDashboardType;
use App\Domains\Analytics\Models\AnalyticsSubject;
use App\Domains\Analytics\Requests\FilterBusinessAnalyticsRequest;
use App\Domains\Analytics\Services\ExecutiveAnalyticsService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExecutiveAnalyticsController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ExecutiveAnalyticsService $executiveAnalyticsService,
    ) {}

    public function overview(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->executiveAnalyticsService->overview(
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            )
        );
    }

    public function ceo(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        return $this->typedDashboard(ExecutiveDashboardType::Ceo, $request);
    }

    public function admin(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        return $this->typedDashboard(ExecutiveDashboardType::Admin, $request);
    }

    public function operations(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        return $this->typedDashboard(ExecutiveDashboardType::Operations, $request);
    }

    public function compliance(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        return $this->typedDashboard(ExecutiveDashboardType::Compliance, $request);
    }

    public function support(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        return $this->typedDashboard(ExecutiveDashboardType::Support, $request);
    }

    public function customer(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        return $this->typedDashboard(ExecutiveDashboardType::Customer, $request);
    }

    public function scorecards(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->executiveAnalyticsService->scorecards(
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            )
        );
    }

    public function trends(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->executiveAnalyticsService->trends(
                $filters['company'] ?? null,
                (string) $request->query('granularity', 'monthly'),
            )
        );
    }

    public function forecast(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->executiveAnalyticsService->forecast(
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
                (int) ($filters['horizon_days'] ?? 14),
            )
        );
    }

    public function widgets(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->executiveAnalyticsService->widgets(
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            )
        );
    }

    public function capture(Request $request): JsonResponse
    {
        $this->authorize('manage', AnalyticsSubject::class);

        return ApiResponse::success(
            $this->executiveAnalyticsService->capture(
                $request->input('company'),
                $request->input('date'),
            ),
            'Executive analytics snapshot captured.'
        );
    }

    protected function typedDashboard(ExecutiveDashboardType $type, FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->executiveAnalyticsService->dashboard(
                $type,
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            )
        );
    }
}

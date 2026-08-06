<?php

namespace App\Domains\Analytics\Controllers;

use App\Domains\Analytics\Models\AnalyticsSubject;
use App\Domains\Analytics\Requests\FilterBusinessAnalyticsRequest;
use App\Domains\Analytics\Services\BusinessAnalyticsService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessAnalyticsController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly BusinessAnalyticsService $businessAnalyticsService,
    ) {}

    public function overview(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->businessAnalyticsService->overview(
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            )
        );
    }

    public function customers(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->businessAnalyticsService->customers(
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            )
        );
    }

    public function revenue(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->businessAnalyticsService->revenue(
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            )
        );
    }

    public function applications(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->businessAnalyticsService->applications(
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            )
        );
    }

    public function growth(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->businessAnalyticsService->growth(
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            )
        );
    }

    public function forecast(FilterBusinessAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);
        $filters = $request->filters();

        return ApiResponse::success(
            $this->businessAnalyticsService->forecast(
                $filters['company'] ?? null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
                (int) ($filters['horizon_days'] ?? 14),
            )
        );
    }

    public function capture(Request $request): JsonResponse
    {
        $this->authorize('manage', AnalyticsSubject::class);

        return ApiResponse::success(
            $this->businessAnalyticsService->capture(
                $request->input('company'),
                $request->input('date'),
            ),
            'Business analytics snapshot captured.'
        );
    }
}

<?php

namespace App\Domains\Analytics\Controllers;

use App\Domains\Analytics\Models\AnalyticsSubject;
use App\Domains\Analytics\Requests\ExportAnalyticsRequest;
use App\Domains\Analytics\Requests\FilterAnalyticsRequest;
use App\Domains\Analytics\Services\PlatformAnalyticsService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PlatformAnalyticsService $analyticsService,
    ) {}

    public function dashboard(FilterAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        return ApiResponse::success(
            $this->analyticsService->dashboard($request->validated())
        );
    }

    public function notifications(FilterAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        return ApiResponse::success(
            $this->analyticsService->notifications($request->validated())
        );
    }

    public function automation(FilterAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        return ApiResponse::success(
            $this->analyticsService->automation($request->validated())
        );
    }

    public function workflows(FilterAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        return ApiResponse::success(
            $this->analyticsService->workflows($request->validated())
        );
    }

    public function ai(FilterAnalyticsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        return ApiResponse::success(
            $this->analyticsService->ai($request->validated())
        );
    }

    public function export(ExportAnalyticsRequest $request): StreamedResponse|JsonResponse
    {
        $this->authorize('export', AnalyticsSubject::class);

        return $this->analyticsService->export($request->validated());
    }
}

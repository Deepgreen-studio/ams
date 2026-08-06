<?php

namespace App\Domains\Applications\Controllers;

use App\Domains\Applications\Requests\IngestApplicationAnalyticsRequest;
use App\Domains\Applications\Resources\ApplicationAnalyticsDailyResource;
use App\Domains\Applications\Services\ApplicationAnalyticsService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationAnalyticsController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ApplicationAnalyticsService $analyticsService
    ) {}

    public function dashboard(Request $request, string $application): JsonResponse
    {
        $app = $this->analyticsService->resolveApplication($application);
        $this->authorize('view', $app);

        $data = $this->analyticsService->dashboard(
            $application,
            $request->query('from'),
            $request->query('to')
        );

        return ApiResponse::success([
            'application' => ['uuid' => $app->uuid, 'name' => $app->name],
            'summary' => $data['summary'],
            'latest' => $data['latest'] ? new ApplicationAnalyticsDailyResource($data['latest']) : null,
            'trend' => $data['trend'],
            'top_countries' => $data['top_countries'],
            'top_devices' => $data['top_devices'],
            'from' => $data['from'],
            'to' => $data['to'],
        ]);
    }

    public function trends(Request $request, string $application): JsonResponse
    {
        $app = $this->analyticsService->resolveApplication($application);
        $this->authorize('view', $app);

        $data = $this->analyticsService->trends(
            $application,
            $request->query('metric'),
            $request->query('from'),
            $request->query('to')
        );

        return ApiResponse::success([
            'application' => ['uuid' => $app->uuid, 'name' => $app->name],
            'metric' => $data['metric'],
            'labels' => $data['labels'],
            'values' => $data['values'],
            'change_percent' => $data['change_percent'],
            'current_total' => $data['current_total'],
            'previous_total' => $data['previous_total'],
            'from' => $data['from'],
            'to' => $data['to'],
        ]);
    }

    public function heatmap(Request $request, string $application): JsonResponse
    {
        $app = $this->analyticsService->resolveApplication($application);
        $this->authorize('view', $app);

        $data = $this->analyticsService->heatmap(
            $application,
            $request->query('from'),
            $request->query('to')
        );

        return ApiResponse::success([
            'application' => ['uuid' => $app->uuid, 'name' => $app->name],
            'days' => $data['days'],
            'hours' => $data['hours'],
            'matrix' => $data['matrix'],
            'max' => $data['max'],
            'from' => $data['from'],
            'to' => $data['to'],
        ]);
    }

    public function countries(Request $request, string $application): JsonResponse
    {
        $app = $this->analyticsService->resolveApplication($application);
        $this->authorize('view', $app);

        $data = $this->analyticsService->countries(
            $application,
            $request->query('from'),
            $request->query('to'),
            (int) $request->query('limit', 25)
        );

        return ApiResponse::success([
            'application' => ['uuid' => $app->uuid, 'name' => $app->name],
            'countries' => $data['countries'],
            'from' => $data['from'],
            'to' => $data['to'],
        ]);
    }

    public function devices(Request $request, string $application): JsonResponse
    {
        $app = $this->analyticsService->resolveApplication($application);
        $this->authorize('view', $app);

        $data = $this->analyticsService->devices(
            $application,
            $request->query('from'),
            $request->query('to'),
            (int) $request->query('limit', 25)
        );

        return ApiResponse::success([
            'application' => ['uuid' => $app->uuid, 'name' => $app->name],
            'devices' => $data['devices'],
            'os_versions' => $data['os_versions'],
            'from' => $data['from'],
            'to' => $data['to'],
        ]);
    }

    public function ingest(IngestApplicationAnalyticsRequest $request, string $application): JsonResponse
    {
        $app = $this->analyticsService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User|null $actor */
        $actor = $request->user();
        $daily = $this->analyticsService->ingest($application, $request->validated(), $actor);

        return ApiResponse::success([
            'daily' => new ApplicationAnalyticsDailyResource($daily),
        ], 'Analytics data ingested successfully.', 201);
    }
}

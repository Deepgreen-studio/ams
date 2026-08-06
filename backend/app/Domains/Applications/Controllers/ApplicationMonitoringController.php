<?php

namespace App\Domains\Applications\Controllers;

use App\Domains\Applications\Requests\IngestApplicationApiErrorRequest;
use App\Domains\Applications\Requests\IngestApplicationCrashRequest;
use App\Domains\Applications\Requests\IngestApplicationHealthRequest;
use App\Domains\Applications\Requests\StoreApplicationCrashRequest;
use App\Domains\Applications\Requests\StoreApplicationMonitoringAlertRequest;
use App\Domains\Applications\Requests\UpdateApplicationCrashRequest;
use App\Domains\Applications\Requests\UpdateApplicationMonitoringAlertRequest;
use App\Domains\Applications\Resources\ApplicationCrashReportCollection;
use App\Domains\Applications\Resources\ApplicationCrashReportResource;
use App\Domains\Applications\Resources\ApplicationHealthMetricResource;
use App\Domains\Applications\Resources\ApplicationMonitoringAlertEventResource;
use App\Domains\Applications\Resources\ApplicationMonitoringAlertResource;
use App\Domains\Applications\Services\ApplicationMonitoringService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationMonitoringController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ApplicationMonitoringService $monitoringService
    ) {}

    public function crashDashboard(Request $request, string $application): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('view', $app);

        $data = $this->monitoringService->crashDashboard(
            $application,
            $request->query('from'),
            $request->query('to')
        );

        return ApiResponse::success([
            'application' => ['uuid' => $app->uuid, 'name' => $app->name],
            'summary' => $data['summary'],
            'recent' => ApplicationCrashReportResource::collection(collect($data['recent'])),
            'chart' => $data['chart'],
            'from' => $data['from'],
            'to' => $data['to'],
        ]);
    }

    public function healthDashboard(Request $request, string $application): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('view', $app);

        $data = $this->monitoringService->healthDashboard(
            $application,
            $request->query('from'),
            $request->query('to')
        );

        return ApiResponse::success([
            'application' => ['uuid' => $app->uuid, 'name' => $app->name],
            'latest' => new ApplicationHealthMetricResource($data['latest']),
            'metrics' => ApplicationHealthMetricResource::collection($data['metrics']),
            'chart' => $data['chart'],
            'from' => $data['from'],
            'to' => $data['to'],
        ]);
    }

    public function charts(Request $request, string $application): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('view', $app);

        $data = $this->monitoringService->charts(
            $application,
            $request->query('metric'),
            $request->query('from'),
            $request->query('to')
        );

        return ApiResponse::success([
            'application' => ['uuid' => $app->uuid, 'name' => $app->name],
            'metric' => $data['metric'],
            'crash_chart' => $data['crash_chart'],
            'health_chart' => $data['health_chart'],
            'from' => $data['from'],
            'to' => $data['to'],
        ]);
    }

    public function deviceStatistics(Request $request, string $application): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('view', $app);

        $stats = $this->monitoringService->deviceStatistics(
            $application,
            (int) $request->query('limit', 20)
        );

        return ApiResponse::success([
            'application' => ['uuid' => $app->uuid, 'name' => $app->name],
            'devices' => $stats,
        ]);
    }

    public function crashes(Request $request, string $application): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('view', $app);

        $crashes = $this->monitoringService->listCrashes($application, $request->only([
            'search', 'type', 'status', 'severity', 'version_label', 'device_os', 'sort_by', 'sort_dir', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'crashes' => (new ApplicationCrashReportCollection($crashes))->resolve(),
        ]);
    }

    public function showCrash(string $application, string $crash): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('view', $app);

        return ApiResponse::success([
            'crash' => new ApplicationCrashReportResource(
                $this->monitoringService->findCrash($application, $crash)
            ),
        ]);
    }

    public function storeCrash(StoreApplicationCrashRequest $request, string $application): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $crash = $this->monitoringService->createCrash($application, $request->validated(), $actor);

        return ApiResponse::success([
            'crash' => new ApplicationCrashReportResource($crash),
        ], 'Crash report created successfully.', 201);
    }

    public function updateCrash(UpdateApplicationCrashRequest $request, string $application, string $crash): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->monitoringService->updateCrash($application, $crash, $request->validated(), $actor);

        return ApiResponse::success([
            'crash' => new ApplicationCrashReportResource($updated),
        ], 'Crash report updated successfully.');
    }

    public function destroyCrash(Request $request, string $application, string $crash): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $this->monitoringService->deleteCrash($application, $crash, $actor);

        return ApiResponse::success([], 'Crash report deleted successfully.');
    }

    public function ingestCrash(IngestApplicationCrashRequest $request, string $application): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User|null $actor */
        $actor = $request->user();
        $crash = $this->monitoringService->ingestCrash($application, $request->validated(), $actor);

        return ApiResponse::success([
            'crash' => new ApplicationCrashReportResource($crash),
        ], 'Crash ingested successfully.', 201);
    }

    public function ingestApiError(IngestApplicationApiErrorRequest $request, string $application): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User|null $actor */
        $actor = $request->user();
        $crash = $this->monitoringService->ingestApiError($application, $request->validated(), $actor);

        return ApiResponse::success([
            'crash' => new ApplicationCrashReportResource($crash),
        ], 'API error ingested successfully.', 201);
    }

    public function ingestHealth(IngestApplicationHealthRequest $request, string $application): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User|null $actor */
        $actor = $request->user();
        $metric = $this->monitoringService->ingestHealth($application, $request->validated(), $actor);

        return ApiResponse::success([
            'metric' => new ApplicationHealthMetricResource($metric),
        ], 'Health metric ingested successfully.', 201);
    }

    public function refreshHealth(Request $request, string $application): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $metric = $this->monitoringService->recordComputedHealth($application, $actor);

        return ApiResponse::success([
            'metric' => new ApplicationHealthMetricResource($metric),
        ], 'Health score refreshed.');
    }

    public function alerts(string $application): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('view', $app);

        return ApiResponse::success([
            'alerts' => ApplicationMonitoringAlertResource::collection(
                $this->monitoringService->listAlerts($application)
            ),
            'events' => ApplicationMonitoringAlertEventResource::collection(
                $this->monitoringService->listAlertEvents($application)
            ),
        ]);
    }

    public function storeAlert(StoreApplicationMonitoringAlertRequest $request, string $application): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $alert = $this->monitoringService->createAlert($application, $request->validated(), $actor);

        return ApiResponse::success([
            'alert' => new ApplicationMonitoringAlertResource($alert),
        ], 'Alert created successfully.', 201);
    }

    public function updateAlert(UpdateApplicationMonitoringAlertRequest $request, string $application, string $alert): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->monitoringService->updateAlert($application, $alert, $request->validated(), $actor);

        return ApiResponse::success([
            'alert' => new ApplicationMonitoringAlertResource($updated),
        ], 'Alert updated successfully.');
    }

    public function destroyAlert(Request $request, string $application, string $alert): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $this->monitoringService->deleteAlert($application, $alert, $actor);

        return ApiResponse::success([], 'Alert deleted successfully.');
    }

    public function acknowledgeAlertEvent(Request $request, string $application, string $event): JsonResponse
    {
        $app = $this->monitoringService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $model = $this->monitoringService->acknowledgeAlertEvent($application, $event, $actor);

        return ApiResponse::success([
            'event' => new ApplicationMonitoringAlertEventResource($model),
        ], 'Alert acknowledged.');
    }
}

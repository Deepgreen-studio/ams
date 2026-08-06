<?php

namespace App\Domains\Monitoring\Controllers;

use App\Domains\Monitoring\Models\MonitoringAlert;
use App\Domains\Monitoring\Models\MonitoringSnapshot;
use App\Domains\Monitoring\Requests\StoreMonitoringAlertRequest;
use App\Domains\Monitoring\Requests\UpdateMonitoringAlertRequest;
use App\Domains\Monitoring\Resources\HealthCheckCollection;
use App\Domains\Monitoring\Resources\MonitoringAlertCollection;
use App\Domains\Monitoring\Resources\MonitoringAlertEventCollection;
use App\Domains\Monitoring\Resources\MonitoringAlertEventResource;
use App\Domains\Monitoring\Resources\MonitoringAlertResource;
use App\Domains\Monitoring\Resources\MonitoringLogCollection;
use App\Domains\Monitoring\Resources\MonitoringSnapshotResource;
use App\Domains\Monitoring\Resources\ServiceStatusResource;
use App\Domains\Monitoring\Services\MonitoringService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly MonitoringService $monitoringService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MonitoringSnapshot::class);
        $data = $this->monitoringService->dashboard(
            $request->query('company'),
            $request->query('integration'),
        );

        return ApiResponse::success($data);
    }

    public function apiMonitor(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MonitoringSnapshot::class);

        return ApiResponse::success(
            $this->monitoringService->apiMonitor($request->query('company'), $request->query('integration'))
        );
    }

    public function webhookMonitor(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MonitoringSnapshot::class);

        return ApiResponse::success(
            $this->monitoringService->webhookMonitor($request->query('company'))
        );
    }

    public function queueHealth(): JsonResponse
    {
        $this->authorize('viewAny', MonitoringSnapshot::class);

        return ApiResponse::success($this->monitoringService->queueHealth());
    }

    public function responseHistory(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MonitoringSnapshot::class);
        $hours = max(1, min((int) $request->query('hours', 24), 168));

        return ApiResponse::success([
            'history' => $this->monitoringService->responseHistory(
                $request->query('company'),
                $request->query('integration'),
                $hours,
            ),
        ]);
    }

    public function capture(Request $request): JsonResponse
    {
        $this->authorize('manage', MonitoringSnapshot::class);
        $result = $this->monitoringService->capture(
            $request->input('company'),
            $request->input('integration'),
        );

        return ApiResponse::success([
            'snapshot' => new MonitoringSnapshotResource($result['snapshot']),
            'events_triggered' => count($result['events']),
            'probes' => $result['probes'] ?? null,
            'metrics' => [
                'health_score' => $result['metrics']['health_score'],
                'performance_score' => $result['metrics']['performance_score'],
                'error_rate' => $result['metrics']['error_rate'],
            ],
        ], 'Health snapshot captured.');
    }

    public function realtime(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MonitoringSnapshot::class);

        return ApiResponse::success(
            $this->monitoringService->realtimeStatus($request->query('company'))
        );
    }

    public function integrations(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MonitoringSnapshot::class);

        return ApiResponse::success(
            $this->monitoringService->integrationStatus($request->query('company'))
        );
    }

    public function timeline(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MonitoringSnapshot::class);

        return ApiResponse::success(
            $this->monitoringService->incidentTimeline($request->only([
                'company', 'company_id', 'level', 'category', 'source', 'search', 'from', 'to', 'limit',
            ]))
        );
    }

    public function healthChecks(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MonitoringSnapshot::class);
        $checks = $this->monitoringService->listHealthChecks($request->only([
            'company', 'company_id', 'check_type', 'status', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'health_checks' => (new HealthCheckCollection($checks))->resolve(),
        ]);
    }

    public function serviceStatuses(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MonitoringSnapshot::class);
        $services = $this->monitoringService->listServiceStatuses($request->only([
            'company', 'company_id', 'service_type', 'status', 'search',
        ]));

        return ApiResponse::success([
            'services' => ServiceStatusResource::collection($services)->resolve(),
        ]);
    }

    public function logs(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MonitoringSnapshot::class);
        $logs = $this->monitoringService->listLogs($request->only([
            'company', 'company_id', 'level', 'category', 'source', 'search', 'from', 'to', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'logs' => (new MonitoringLogCollection($logs))->resolve(),
        ]);
    }

    public function alerts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MonitoringAlert::class);
        $alerts = $this->monitoringService->listAlerts($request->only([
            'search', 'metric', 'is_enabled', 'company', 'company_id', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'alerts' => (new MonitoringAlertCollection($alerts))->resolve(),
        ]);
    }

    public function storeAlert(StoreMonitoringAlertRequest $request): JsonResponse
    {
        $this->authorize('create', MonitoringAlert::class);
        /** @var User $actor */
        $actor = $request->user();
        $alert = $this->monitoringService->createAlert($request->validated(), $actor);

        return ApiResponse::success([
            'alert' => new MonitoringAlertResource($alert),
        ], 'Alert configuration created.', 201);
    }

    public function showAlert(string $alert): JsonResponse
    {
        $model = $this->monitoringService->showAlert($alert);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'alert' => new MonitoringAlertResource($model),
        ]);
    }

    public function updateAlert(UpdateMonitoringAlertRequest $request, string $alert): JsonResponse
    {
        $model = $this->monitoringService->showAlert($alert);
        $this->authorize('update', $model);
        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->monitoringService->updateAlert($alert, $request->validated(), $actor);

        return ApiResponse::success([
            'alert' => new MonitoringAlertResource($updated),
        ], 'Alert configuration updated.');
    }

    public function destroyAlert(string $alert): JsonResponse
    {
        $model = $this->monitoringService->showAlert($alert);
        $this->authorize('delete', $model);
        $this->monitoringService->deleteAlert($alert);

        return ApiResponse::success(null, 'Alert configuration deleted.');
    }

    public function alertEvents(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MonitoringAlert::class);
        $events = $this->monitoringService->listAlertEvents($request->only([
            'status', 'severity', 'search', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'events' => (new MonitoringAlertEventCollection($events))->resolve(),
        ]);
    }

    public function acknowledgeEvent(Request $request, string $event): JsonResponse
    {
        $this->authorize('manage', MonitoringAlert::class);
        /** @var User $actor */
        $actor = $request->user();
        $model = $this->monitoringService->acknowledgeEvent($event, $actor);

        return ApiResponse::success([
            'event' => new MonitoringAlertEventResource($model),
        ], 'Alert event acknowledged.');
    }
}

<?php

namespace App\Domains\Monitoring\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Integrations\Repositories\IntegrationRepository;
use App\Domains\Monitoring\Models\MonitoringAlert;
use App\Domains\Monitoring\Repositories\HealthCheckRepository;
use App\Domains\Monitoring\Repositories\MonitoringAlertEventRepository;
use App\Domains\Monitoring\Repositories\MonitoringAlertRepository;
use App\Domains\Monitoring\Repositories\MonitoringLogRepository;
use App\Domains\Monitoring\Repositories\MonitoringSnapshotRepository;
use App\Domains\Monitoring\Repositories\ServiceStatusRepository;
use App\Domains\Queue\Models\QueueJobTrack;
use App\Models\User;
use App\Shared\Services\Monitoring\HealthMonitor;
use App\Shared\Services\Monitoring\MetricsAggregator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MonitoringService
{
    public function __construct(
        private readonly HealthMonitor $healthMonitor,
        private readonly MetricsAggregator $metricsAggregator,
        private readonly MonitoringSnapshotRepository $snapshotRepository,
        private readonly MonitoringAlertRepository $alertRepository,
        private readonly MonitoringAlertEventRepository $eventRepository,
        private readonly HealthCheckRepository $healthCheckRepository,
        private readonly ServiceStatusRepository $serviceStatusRepository,
        private readonly MonitoringLogRepository $logRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly IntegrationRepository $integrationRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?string $company = null, ?string $integration = null): array
    {
        [$companyId, $integrationId] = $this->resolveScope($company, $integration);
        $live = $this->healthMonitor->inspect($companyId, $integrationId);
        $history = $this->metricsAggregator->responseHistory($companyId, $integrationId, 24);
        $snapshots = $this->snapshotRepository->recent(24, $companyId, $integrationId);

        return [
            'scores' => [
                'health_score' => $live['health_score'],
                'performance_score' => $live['performance_score'],
                'uptime_percent' => $live['uptime_percent'],
                'downtime_percent' => $live['downtime_percent'],
                'error_rate' => $live['error_rate'],
            ],
            'statuses' => [
                'availability' => $live['availability_status'],
                'authentication' => $live['authentication_status'],
                'rate_limits' => $live['rate_limit_status'],
                'server' => $live['server_status'],
                'queue' => $live['queue']['status'] ?? 'unknown',
            ],
            'api' => $live['api'],
            'webhooks' => $live['webhooks'],
            'queue' => $live['queue'],
            'charts' => [
                'response_history' => $history,
                'health_trend' => $snapshots->map(fn ($s) => [
                    'bucket' => optional($s->created_at)->toIso8601String(),
                    'health_score' => $s->health_score,
                    'performance_score' => $s->performance_score,
                    'error_rate' => $s->error_rate,
                    'avg_response_ms' => $s->avg_response_ms,
                ])->reverse()->values()->all(),
            ],
            'period' => [
                'start' => $live['period_start'],
                'end' => $live['period_end'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function apiMonitor(?string $company = null, ?string $integration = null): array
    {
        [$companyId, $integrationId] = $this->resolveScope($company, $integration);
        $live = $this->healthMonitor->inspect($companyId, $integrationId);

        return [
            'summary' => $live['api'],
            'availability' => $live['availability'],
            'authentication' => $live['authentication'],
            'rate_limits' => $live['rate_limits'],
            'avg_response_ms' => $live['avg_response_ms'],
            'statuses' => [
                'availability' => $live['availability_status'],
                'authentication' => $live['authentication_status'],
                'rate_limits' => $live['rate_limit_status'],
                'server' => $live['server_status'],
            ],
            'history' => $this->metricsAggregator->responseHistory($companyId, $integrationId, 48),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function webhookMonitor(?string $company = null): array
    {
        [$companyId] = $this->resolveScope($company, null);
        $live = $this->healthMonitor->inspect($companyId, null);

        return [
            'summary' => $live['webhooks'],
            'success_rate' => $live['webhook_success_rate'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function queueHealth(): array
    {
        $live = $this->healthMonitor->inspect(null, null);
        $jobs = [
            'running' => QueueJobTrack::query()->where('status', 'running')->latest()->limit(20)->get()
                ->map(fn (QueueJobTrack $job): array => $this->mapJobTrack($job))->values()->all(),
            'recent_failed' => QueueJobTrack::query()->where('status', 'failed')->latest('failed_at')->limit(20)->get()
                ->map(fn (QueueJobTrack $job): array => $this->mapJobTrack($job))->values()->all(),
        ];

        return array_merge($live['queue'] ?? [], [
            'jobs' => $jobs,
        ]);
    }

    /**
     * Live real-time status board from latest service_status + live metrics.
     *
     * @return array<string, mixed>
     */
    public function realtimeStatus(?string $company = null): array
    {
        [$companyId] = $this->resolveScope($company, null);
        $live = $this->healthMonitor->inspect($companyId, null);
        $services = $this->serviceStatusRepository->listFiltered(['company_id' => $companyId]);
        $checks = $this->healthCheckRepository->latestByType($companyId);

        return [
            'generated_at' => now()->toIso8601String(),
            'scores' => [
                'health_score' => $live['health_score'],
                'performance_score' => $live['performance_score'],
                'error_rate' => $live['error_rate'],
                'queue_health_score' => $live['queue_health_score'],
            ],
            'statuses' => [
                'availability' => $live['availability_status'],
                'authentication' => $live['authentication_status'],
                'rate_limits' => $live['rate_limit_status'],
                'server' => $live['server_status'],
                'queue' => $live['queue']['status'] ?? 'unknown',
            ],
            'services' => $services->map(fn ($service) => [
                'uuid' => $service->uuid,
                'service_key' => $service->service_key,
                'service_type' => $service->service_type?->value ?? $service->service_type,
                'name' => $service->name,
                'status' => $service->status?->value ?? $service->status,
                'last_check_at' => $service->last_check_at,
                'consecutive_failures' => $service->consecutive_failures,
                'avg_response_ms' => $service->avg_response_ms,
                'error_rate' => $service->error_rate,
            ])->values()->all(),
            'health_checks' => $checks->map(fn ($check) => [
                'uuid' => $check->uuid,
                'check_type' => $check->check_type?->value ?? $check->check_type,
                'name' => $check->name,
                'status' => $check->status?->value ?? $check->status,
                'response_ms' => $check->response_ms,
                'message' => $check->message,
                'checked_at' => $check->checked_at,
            ])->values()->all(),
            'api' => $live['api'],
            'webhooks' => $live['webhooks'],
            'queue' => $live['queue'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function integrationStatus(?string $company = null): array
    {
        [$companyId] = $this->resolveScope($company, null);
        $live = $this->healthMonitor->inspect($companyId, null);
        $integrations = Integration::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get([
                'uuid', 'name', 'slug', 'type', 'status', 'health_status',
                'base_url', 'last_health_check', 'updated_at', 'company_id',
            ]);

        $service = $this->serviceStatusRepository->filteredQuery(['company_id' => $companyId])
            ->where('service_key', 'integrations')
            ->first();

        return [
            'summary' => $live['availability'] ?? [],
            'server_status' => $live['server_status'] ?? 'unknown',
            'service' => $service ? [
                'uuid' => $service->uuid,
                'status' => $service->status?->value ?? $service->status,
                'last_check_at' => $service->last_check_at,
                'consecutive_failures' => $service->consecutive_failures,
                'message' => $service->metadata['message'] ?? null,
            ] : null,
            'integrations' => $integrations->map(fn (Integration $item): array => [
                'uuid' => $item->uuid,
                'name' => $item->name,
                'slug' => $item->slug,
                'type' => $item->type instanceof \BackedEnum ? $item->type->value : $item->type,
                'status' => $item->status instanceof \BackedEnum ? $item->status->value : $item->status,
                'health_status' => $item->health_status instanceof \BackedEnum ? $item->health_status->value : $item->health_status,
                'base_url' => $item->base_url,
                'last_health_check' => optional($item->last_health_check)?->toIso8601String(),
                'updated_at' => optional($item->updated_at)?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function incidentTimeline(array $filters = []): array
    {
        $this->normalizeCompanyFilter($filters);
        $limit = max(1, min((int) ($filters['limit'] ?? 50), 200));

        $logs = $this->logRepository->timeline($filters, $limit);
        $events = $this->eventRepository->paginateFiltered(array_merge($filters, ['per_page' => $limit]));

        $timeline = collect($logs)->map(fn ($log): array => [
            'kind' => 'log',
            'uuid' => $log->uuid,
            'level' => $log->level?->value ?? $log->level,
            'category' => $log->category?->value ?? $log->category,
            'title' => $log->title ?: $log->source,
            'message' => $log->message,
            'occurred_at' => optional($log->occurred_at)?->toIso8601String(),
            'context' => $log->context,
        ])->concat(
            collect($events->items())->map(fn ($event): array => [
                'kind' => 'alert',
                'uuid' => $event->uuid,
                'level' => $event->severity,
                'category' => 'incident',
                'title' => $event->alert?->name ?? 'Alert event',
                'message' => $event->message,
                'occurred_at' => optional($event->created_at)?->toIso8601String(),
                'context' => [
                    'status' => $event->status,
                    'metric_value' => $event->metric_value,
                ],
            ])
        )->sortByDesc('occurred_at')->values()->take($limit)->all();

        return [
            'items' => $timeline,
            'meta' => ['total' => count($timeline), 'limit' => $limit],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listHealthChecks(array $filters = []): LengthAwarePaginator
    {
        $this->normalizeCompanyFilter($filters);

        return $this->healthCheckRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Support\Collection<int, \App\Domains\Monitoring\Models\ServiceStatus>
     */
    public function listServiceStatuses(array $filters = [])
    {
        $this->normalizeCompanyFilter($filters);

        return $this->serviceStatusRepository->listFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listLogs(array $filters = []): LengthAwarePaginator
    {
        $this->normalizeCompanyFilter($filters);

        return $this->logRepository->paginateFiltered($filters);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function responseHistory(?string $company = null, ?string $integration = null, int $hours = 24): array
    {
        [$companyId, $integrationId] = $this->resolveScope($company, $integration);

        return $this->metricsAggregator->responseHistory($companyId, $integrationId, $hours);
    }

    /**
     * @return array<string, mixed>
     */
    public function capture(?string $company = null, ?string $integration = null): array
    {
        [$companyId, $integrationId] = $this->resolveScope($company, $integration);

        return $this->healthMonitor->captureSnapshot($companyId, $integrationId);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listAlerts(array $filters = []): LengthAwarePaginator
    {
        $this->normalizeCompanyFilter($filters);

        return $this->alertRepository->paginateFiltered($filters);
    }

    public function showAlert(string $uuid): MonitoringAlert
    {
        return $this->alertRepository->findByUuidOrFail($uuid)->load(['company:id,uuid,company_name', 'creator:id,uuid,full_name,email']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAlert(array $data, User $actor): MonitoringAlert
    {
        return DB::transaction(function () use ($data, $actor): MonitoringAlert {
            $payload = $this->prepareAlertPayload($data);
            if (! empty($data['company_id'])) {
                $payload['company_id'] = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id'])->id;
            }
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;
            $payload['is_enabled'] = $payload['is_enabled'] ?? true;
            $payload['cooldown_minutes'] = $payload['cooldown_minutes'] ?? 15;
            $payload['channels'] = $payload['channels'] ?? ['in_app'];
            $payload['operator'] = $payload['operator'] ?? 'gte';

            return $this->alertRepository->createAlert($payload);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAlert(string $uuid, array $data, User $actor): MonitoringAlert
    {
        return DB::transaction(function () use ($uuid, $data, $actor): MonitoringAlert {
            $alert = $this->alertRepository->findByUuidOrFail($uuid);
            $payload = $this->prepareAlertPayload($data, true);
            $payload['updated_by'] = $actor->id;

            return $this->alertRepository->updateAlert($alert, $payload);
        });
    }

    public function deleteAlert(string $uuid): void
    {
        $this->alertRepository->findByUuidOrFail($uuid)->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listAlertEvents(array $filters = []): LengthAwarePaginator
    {
        return $this->eventRepository->paginateFiltered($filters);
    }

    public function acknowledgeEvent(string $uuid, User $actor): \App\Domains\Monitoring\Models\MonitoringAlertEvent
    {
        $event = $this->eventRepository->findByUuidOrFail($uuid);
        $event->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $actor->id,
        ]);

        return $event->fresh(['alert', 'acknowledger']);
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    protected function resolveScope(?string $company, ?string $integration): array
    {
        $companyId = null;
        $integrationId = null;

        if ($company) {
            $companyId = $this->companyRepository->findByIdentifierOrFail($company)->id;
        }
        if ($integration) {
            $integrationId = $this->integrationRepository->findByIdentifierOrFail($integration)->id;
        }

        return [$companyId, $integrationId];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapJobTrack(QueueJobTrack $job): array
    {
        return [
            'uuid' => $job->uuid,
            'display_name' => $job->display_name,
            'job_class' => $job->job_class,
            'queue' => $job->queue,
            'status' => $job->status instanceof \BackedEnum ? $job->status->value : $job->status,
            'attempts' => $job->attempts,
            'exception' => $job->exception,
            'started_at' => optional($job->started_at)?->toIso8601String(),
            'finished_at' => optional($job->finished_at)?->toIso8601String(),
            'failed_at' => optional($job->failed_at)?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareAlertPayload(array $data, bool $partial = false): array
    {
        $keys = ['name', 'metric', 'operator', 'threshold', 'is_enabled', 'cooldown_minutes', 'channels', 'description'];
        $payload = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function normalizeCompanyFilter(array &$filters): void
    {
        $identifier = $filters['company'] ?? $filters['company_id'] ?? null;
        if (! $identifier || is_numeric($identifier)) {
            return;
        }
        $filters['company_id'] = $this->companyRepository->findByIdentifierOrFail((string) $identifier)->id;
        unset($filters['company']);
    }
}

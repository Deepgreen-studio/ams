<?php

namespace App\Domains\Monitoring\Services;

use App\Domains\Audit\Models\ApiLog;
use App\Domains\Audit\Models\ErrorLog;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Monitoring\Enums\HealthCheckStatus;
use App\Domains\Monitoring\Enums\HealthCheckType;
use App\Domains\Monitoring\Enums\MonitoringLogCategory;
use App\Domains\Monitoring\Enums\MonitoringLogLevel;
use App\Domains\Monitoring\Enums\ServiceType;
use App\Domains\Monitoring\Models\MonitoringSnapshot;
use App\Domains\Monitoring\Repositories\HealthCheckRepository;
use App\Domains\Monitoring\Repositories\MonitoringLogRepository;
use App\Domains\Monitoring\Repositories\ServiceStatusRepository;
use App\Domains\Queue\Models\QueueJobTrack;
use App\Domains\Scheduler\Models\ScheduledJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Runs discrete enterprise health probes and persists checks, service status, and logs.
 */
class EnterpriseHealthProbeService
{
    public function __construct(
        private readonly HealthCheckRepository $healthCheckRepository,
        private readonly ServiceStatusRepository $serviceStatusRepository,
        private readonly MonitoringLogRepository $logRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $metrics  Aggregated hub metrics from MetricsAggregator
     * @return array{
     *     checks: list<\App\Domains\Monitoring\Models\HealthCheck>,
     *     services: list<\App\Domains\Monitoring\Models\ServiceStatus>,
     *     logs: list<\App\Domains\Monitoring\Models\MonitoringLog>
     * }
     */
    public function probe(?int $companyId, ?MonitoringSnapshot $snapshot, array $metrics): array
    {
        $checks = [];
        $services = [];
        $logs = [];
        $now = now();

        $probes = [
            $this->probeDatabase($companyId, $snapshot, $now),
            $this->probeCache($companyId, $snapshot, $now),
            $this->probeApi($companyId, $snapshot, $metrics, $now),
            $this->probeWebhooks($companyId, $snapshot, $metrics, $now),
            $this->probeQueue($companyId, $snapshot, $metrics, $now),
            $this->probeJobs($companyId, $snapshot, $metrics, $now),
            $this->probeIntegrations($companyId, $snapshot, $metrics, $now),
            $this->probeServer($companyId, $snapshot, $metrics, $now),
            $this->probeApplication($companyId, $snapshot, $metrics, $now),
            $this->probeScheduler($companyId, $snapshot, $now),
        ];

        foreach ($probes as $probe) {
            $checks[] = $probe['check'];
            $services[] = $probe['service'];
            if ($probe['log'] !== null) {
                $logs[] = $probe['log'];
            }
        }

        return [
            'checks' => $checks,
            'services' => $services,
            'logs' => $logs,
        ];
    }

    /**
     * @param  list<\App\Domains\Monitoring\Models\MonitoringAlertEvent>  $events
     */
    public function logIncidents(?int $companyId, array $events): void
    {
        foreach ($events as $event) {
            $this->logRepository->create([
                'company_id' => $companyId,
                'level' => match ((string) ($event->severity ?? 'warning')) {
                    'critical' => MonitoringLogLevel::Critical->value,
                    'error' => MonitoringLogLevel::Error->value,
                    default => MonitoringLogLevel::Warning->value,
                },
                'category' => MonitoringLogCategory::Incident->value,
                'source' => 'alert_evaluator',
                'title' => 'Alert triggered',
                'message' => (string) $event->message,
                'context' => [
                    'event_uuid' => $event->uuid,
                    'severity' => $event->severity,
                    'metric_value' => $event->metric_value,
                    'alert_id' => $event->monitoring_alert_id,
                ],
                'related_type' => $event::class,
                'related_id' => $event->id,
                'occurred_at' => $event->created_at ?? now(),
            ]);
        }
    }

    /**
     * @return array{check: \App\Domains\Monitoring\Models\HealthCheck, service: \App\Domains\Monitoring\Models\ServiceStatus, log: ?\App\Domains\Monitoring\Models\MonitoringLog}
     */
    protected function probeDatabase(?int $companyId, ?MonitoringSnapshot $snapshot, $now): array
    {
        $started = microtime(true);
        $status = HealthCheckStatus::Healthy->value;
        $message = 'Database connection OK';

        try {
            DB::select('select 1 as ok');
        } catch (Throwable $exception) {
            $status = HealthCheckStatus::Unhealthy->value;
            $message = 'Database connection failed: '.$exception->getMessage();
        }

        $ms = (int) round((microtime(true) - $started) * 1000);

        return $this->persistProbe(
            $companyId,
            $snapshot,
            HealthCheckType::Database,
            ServiceType::Database,
            'database',
            'Database',
            $status,
            $ms,
            $message,
            ['driver' => DB::connection()->getDriverName()],
            $now
        );
    }

    /**
     * @return array{check: \App\Domains\Monitoring\Models\HealthCheck, service: \App\Domains\Monitoring\Models\ServiceStatus, log: ?\App\Domains\Monitoring\Models\MonitoringLog}
     */
    protected function probeCache(?int $companyId, ?MonitoringSnapshot $snapshot, $now): array
    {
        $started = microtime(true);
        $status = HealthCheckStatus::Healthy->value;
        $message = 'Cache store OK';
        $key = 'ams:monitoring:health:'.uniqid('', true);

        try {
            Cache::put($key, '1', 10);
            $ok = Cache::get($key) === '1';
            Cache::forget($key);
            if (! $ok) {
                $status = HealthCheckStatus::Degraded->value;
                $message = 'Cache read/write mismatch';
            }
        } catch (Throwable $exception) {
            $status = HealthCheckStatus::Unhealthy->value;
            $message = 'Cache probe failed: '.$exception->getMessage();
        }

        $ms = (int) round((microtime(true) - $started) * 1000);

        return $this->persistProbe(
            $companyId,
            $snapshot,
            HealthCheckType::Cache,
            ServiceType::Cache,
            'cache',
            'Cache',
            $status,
            $ms,
            $message,
            ['store' => config('cache.default')],
            $now
        );
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array{check: \App\Domains\Monitoring\Models\HealthCheck, service: \App\Domains\Monitoring\Models\ServiceStatus, log: ?\App\Domains\Monitoring\Models\MonitoringLog}
     */
    protected function probeApi(?int $companyId, ?MonitoringSnapshot $snapshot, array $metrics, $now): array
    {
        $api = $metrics['api'] ?? [];
        $platform = $this->platformApiMetrics();
        $errorRate = (float) ($api['error_rate'] ?? 0);
        $avgMs = (int) ($api['avg_response_ms'] ?? 0);
        $platformErrors = (int) ($platform['error_count'] ?? 0);

        $status = match (true) {
            $errorRate >= 15 || $platformErrors > 50 => HealthCheckStatus::Unhealthy->value,
            $errorRate >= 5 || $avgMs >= 2000 || $platformErrors > 10 => HealthCheckStatus::Degraded->value,
            default => HealthCheckStatus::Healthy->value,
        };

        $message = sprintf(
            'API avg %dms · error rate %.2f%% · platform errors %d',
            $avgMs,
            $errorRate,
            $platformErrors
        );

        return $this->persistProbe(
            $companyId,
            $snapshot,
            HealthCheckType::Api,
            ServiceType::Api,
            'api',
            'API Gateway',
            $status,
            $avgMs,
            $message,
            [
                'integration_api' => $api,
                'platform_api' => $platform,
            ],
            $now,
            MonitoringLogCategory::Api
        );
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array{check: \App\Domains\Monitoring\Models\HealthCheck, service: \App\Domains\Monitoring\Models\ServiceStatus, log: ?\App\Domains\Monitoring\Models\MonitoringLog}
     */
    protected function probeWebhooks(?int $companyId, ?MonitoringSnapshot $snapshot, array $metrics, $now): array
    {
        $webhooks = $metrics['webhooks'] ?? [];
        $rate = (float) ($webhooks['success_rate'] ?? 100);
        $status = match (true) {
            $rate < 80 => HealthCheckStatus::Unhealthy->value,
            $rate < 95 => HealthCheckStatus::Degraded->value,
            default => HealthCheckStatus::Healthy->value,
        };

        return $this->persistProbe(
            $companyId,
            $snapshot,
            HealthCheckType::Webhook,
            ServiceType::Webhook,
            'webhooks',
            'Webhook Delivery',
            $status,
            (int) ($webhooks['avg_duration_ms'] ?? 0),
            sprintf('Webhook success rate %.2f%% (%d failed)', $rate, (int) ($webhooks['failed'] ?? 0)),
            $webhooks,
            $now,
            MonitoringLogCategory::Webhook
        );
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array{check: \App\Domains\Monitoring\Models\HealthCheck, service: \App\Domains\Monitoring\Models\ServiceStatus, log: ?\App\Domains\Monitoring\Models\MonitoringLog}
     */
    protected function probeQueue(?int $companyId, ?MonitoringSnapshot $snapshot, array $metrics, $now): array
    {
        $queue = $metrics['queue'] ?? [];
        $status = (string) ($queue['status'] ?? HealthCheckStatus::Unknown->value);

        return $this->persistProbe(
            $companyId,
            $snapshot,
            HealthCheckType::Queue,
            ServiceType::Queue,
            'queue',
            'Queue Worker',
            $status,
            null,
            sprintf(
                'Queue %s · pending %d · failed %d · running %d',
                $status,
                (int) ($queue['pending'] ?? 0),
                (int) ($queue['failed'] ?? 0),
                (int) ($queue['running'] ?? 0)
            ),
            $queue,
            $now,
            MonitoringLogCategory::Queue
        );
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array{check: \App\Domains\Monitoring\Models\HealthCheck, service: \App\Domains\Monitoring\Models\ServiceStatus, log: ?\App\Domains\Monitoring\Models\MonitoringLog}
     */
    protected function probeJobs(?int $companyId, ?MonitoringSnapshot $snapshot, array $metrics, $now): array
    {
        $failedPeriod = (int) (($metrics['queue']['failed_period'] ?? 0));
        $running = QueueJobTrack::query()->where('status', 'running')->count();
        $recentFailed = QueueJobTrack::query()
            ->where('status', 'failed')
            ->where('failed_at', '>=', now()->subHour())
            ->count();

        $status = match (true) {
            $recentFailed >= 20 => HealthCheckStatus::Unhealthy->value,
            $recentFailed >= 5 || $failedPeriod >= 10 => HealthCheckStatus::Degraded->value,
            default => HealthCheckStatus::Healthy->value,
        };

        return $this->persistProbe(
            $companyId,
            $snapshot,
            HealthCheckType::Job,
            ServiceType::Job,
            'jobs',
            'Background Jobs',
            $status,
            null,
            sprintf('Running %d · failed (1h) %d · failed (period) %d', $running, $recentFailed, $failedPeriod),
            ['running' => $running, 'failed_1h' => $recentFailed, 'failed_period' => $failedPeriod],
            $now,
            MonitoringLogCategory::Job
        );
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array{check: \App\Domains\Monitoring\Models\HealthCheck, service: \App\Domains\Monitoring\Models\ServiceStatus, log: ?\App\Domains\Monitoring\Models\MonitoringLog}
     */
    protected function probeIntegrations(?int $companyId, ?MonitoringSnapshot $snapshot, array $metrics, $now): array
    {
        $availability = $metrics['availability'] ?? [];
        $total = (int) ($availability['integrations_total'] ?? 0);
        $healthy = (int) ($availability['integrations_healthy'] ?? 0);
        $rate = $total > 0 ? ($healthy / $total) * 100 : 100.0;
        $status = match (true) {
            $total === 0 => HealthCheckStatus::Unknown->value,
            $rate < 70 => HealthCheckStatus::Unhealthy->value,
            $rate < 90 => HealthCheckStatus::Degraded->value,
            default => HealthCheckStatus::Healthy->value,
        };

        $items = Integration::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get(['uuid', 'name', 'health_status', 'status', 'last_health_check', 'updated_at'])
            ->map(fn (Integration $integration): array => [
                'uuid' => $integration->uuid,
                'name' => $integration->name,
                'health_status' => $integration->health_status instanceof \BackedEnum
                    ? $integration->health_status->value
                    : $integration->health_status,
                'status' => $integration->status instanceof \BackedEnum
                    ? $integration->status->value
                    : $integration->status,
                'last_health_check' => optional($integration->last_health_check)?->toIso8601String(),
            ])
            ->all();

        return $this->persistProbe(
            $companyId,
            $snapshot,
            HealthCheckType::Integration,
            ServiceType::Integration,
            'integrations',
            'Integrations',
            $status,
            null,
            sprintf('%d/%d integrations healthy', $healthy, $total),
            ['integrations' => $items, 'summary' => $availability],
            $now,
            MonitoringLogCategory::Integration
        );
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array{check: \App\Domains\Monitoring\Models\HealthCheck, service: \App\Domains\Monitoring\Models\ServiceStatus, log: ?\App\Domains\Monitoring\Models\MonitoringLog}
     */
    protected function probeServer(?int $companyId, ?MonitoringSnapshot $snapshot, array $metrics, $now): array
    {
        $status = (string) ($metrics['server_status'] ?? HealthCheckStatus::Unknown->value);
        $memory = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);

        return $this->persistProbe(
            $companyId,
            $snapshot,
            HealthCheckType::Server,
            ServiceType::Server,
            'server',
            'Application Server',
            $status,
            null,
            sprintf('Server %s · PHP %s · memory %s', $status, PHP_VERSION, $this->formatBytes($memory)),
            [
                'php_version' => PHP_VERSION,
                'memory_bytes' => $memory,
                'memory_peak_bytes' => $peak,
                'os' => PHP_OS_FAMILY,
            ],
            $now,
            MonitoringLogCategory::Server
        );
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array{check: \App\Domains\Monitoring\Models\HealthCheck, service: \App\Domains\Monitoring\Models\ServiceStatus, log: ?\App\Domains\Monitoring\Models\MonitoringLog}
     */
    protected function probeApplication(?int $companyId, ?MonitoringSnapshot $snapshot, array $metrics, $now): array
    {
        $health = (int) ($metrics['health_score'] ?? 0);
        $status = match (true) {
            $health >= 85 => HealthCheckStatus::Healthy->value,
            $health >= 60 => HealthCheckStatus::Degraded->value,
            default => HealthCheckStatus::Unhealthy->value,
        };

        return $this->persistProbe(
            $companyId,
            $snapshot,
            HealthCheckType::Application,
            ServiceType::Application,
            'application',
            'Application Health',
            $status,
            null,
            sprintf('Application health score %d · performance %d', $health, (int) ($metrics['performance_score'] ?? 0)),
            [
                'health_score' => $health,
                'performance_score' => (int) ($metrics['performance_score'] ?? 0),
                'uptime_percent' => $metrics['uptime_percent'] ?? null,
                'error_rate' => $metrics['error_rate'] ?? null,
            ],
            $now,
            MonitoringLogCategory::Health
        );
    }

    /**
     * @return array{check: \App\Domains\Monitoring\Models\HealthCheck, service: \App\Domains\Monitoring\Models\ServiceStatus, log: ?\App\Domains\Monitoring\Models\MonitoringLog}
     */
    protected function probeScheduler(?int $companyId, ?MonitoringSnapshot $snapshot, $now): array
    {
        $enabled = 0;
        $overdue = 0;

        if (Schema::hasTable('scheduled_jobs')) {
            $enabled = ScheduledJob::query()->where('is_enabled', true)->count();
            $overdue = ScheduledJob::query()
                ->where('is_enabled', true)
                ->whereNotNull('next_run_at')
                ->where('next_run_at', '<', now()->subMinutes(30))
                ->count();
        }

        $status = match (true) {
            $overdue >= 5 => HealthCheckStatus::Unhealthy->value,
            $overdue >= 1 => HealthCheckStatus::Degraded->value,
            default => HealthCheckStatus::Healthy->value,
        };

        return $this->persistProbe(
            $companyId,
            $snapshot,
            HealthCheckType::Scheduler,
            ServiceType::Scheduler,
            'scheduler',
            'Scheduler',
            $status,
            null,
            sprintf('%d enabled jobs · %d overdue', $enabled, $overdue),
            ['enabled' => $enabled, 'overdue' => $overdue],
            $now
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{check: \App\Domains\Monitoring\Models\HealthCheck, service: \App\Domains\Monitoring\Models\ServiceStatus, log: ?\App\Domains\Monitoring\Models\MonitoringLog}
     */
    protected function persistProbe(
        ?int $companyId,
        ?MonitoringSnapshot $snapshot,
        HealthCheckType $checkType,
        ServiceType $serviceType,
        string $serviceKey,
        string $name,
        string $status,
        ?int $responseMs,
        string $message,
        array $metadata,
        $now,
        ?MonitoringLogCategory $logCategory = null
    ): array {
        /** @var \App\Domains\Monitoring\Models\HealthCheck $check */
        $check = $this->healthCheckRepository->create([
            'company_id' => $companyId,
            'monitoring_snapshot_id' => $snapshot?->id,
            'check_type' => $checkType->value,
            'name' => $name,
            'status' => $status,
            'response_ms' => $responseMs,
            'message' => $message,
            'metadata' => $metadata,
            'checked_at' => $now,
        ]);

        $existing = $this->serviceStatusRepository->filteredQuery([
            'company_id' => $companyId,
        ])->where('service_key', $serviceKey)->first();

        $consecutive = 0;
        if (in_array($status, [HealthCheckStatus::Unhealthy->value, HealthCheckStatus::Degraded->value], true)) {
            $consecutive = ((int) ($existing?->consecutive_failures ?? 0)) + 1;
        }

        $service = $this->serviceStatusRepository->upsertByKey($companyId, $serviceKey, [
            'service_type' => $serviceType->value,
            'name' => $name,
            'status' => $status,
            'last_check_at' => $now,
            'last_success_at' => $status === HealthCheckStatus::Healthy->value
                ? $now
                : $existing?->last_success_at,
            'last_failure_at' => $status !== HealthCheckStatus::Healthy->value
                ? $now
                : $existing?->last_failure_at,
            'consecutive_failures' => $consecutive,
            'uptime_percent' => $metadata['uptime_percent'] ?? $metadata['summary']['uptime_percent'] ?? null,
            'avg_response_ms' => $responseMs,
            'error_rate' => $metadata['error_rate'] ?? $metadata['integration_api']['error_rate'] ?? null,
            'metadata' => $metadata,
        ]);

        $log = null;
        if ($status !== HealthCheckStatus::Healthy->value) {
            $level = $status === HealthCheckStatus::Unhealthy->value
                ? MonitoringLogLevel::Error->value
                : MonitoringLogLevel::Warning->value;

            /** @var \App\Domains\Monitoring\Models\MonitoringLog $log */
            $log = $this->logRepository->create([
                'company_id' => $companyId,
                'level' => $level,
                'category' => ($logCategory ?? MonitoringLogCategory::Health)->value,
                'source' => $serviceKey,
                'title' => $name.' '.$status,
                'message' => $message,
                'context' => [
                    'check_uuid' => $check->uuid,
                    'service_uuid' => $service->uuid,
                    'status' => $status,
                    'metadata' => $metadata,
                ],
                'related_type' => $check::class,
                'related_id' => $check->id,
                'occurred_at' => $now,
            ]);
        }

        return compact('check', 'service', 'log');
    }

    /**
     * @return array<string, mixed>
     */
    protected function platformApiMetrics(): array
    {
        if (! Schema::hasTable('api_logs')) {
            return ['total' => 0, 'error_count' => 0, 'avg_duration' => 0];
        }

        $from = now()->subDay();
        $query = ApiLog::query()->where('created_at', '>=', $from);
        $total = (clone $query)->count();
        $errors = (clone $query)->where('response_code', '>=', 400)->count();
        $avg = (int) round((float) ((clone $query)->avg('duration') ?? 0));
        $errorLogs = Schema::hasTable('error_logs')
            ? ErrorLog::query()->where('created_at', '>=', $from)->count()
            : 0;

        return [
            'total' => $total,
            'error_count' => $errors,
            'avg_duration' => $avg,
            'application_errors' => $errorLogs,
        ];
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, 1).' '.$units[$i];
    }
}

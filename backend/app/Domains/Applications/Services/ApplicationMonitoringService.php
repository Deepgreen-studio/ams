<?php

namespace App\Domains\Applications\Services;

use App\Domains\Applications\Enums\ApplicationCrashSeverity;
use App\Domains\Applications\Enums\ApplicationCrashStatus;
use App\Domains\Applications\Enums\ApplicationCrashType;
use App\Domains\Applications\Enums\ApplicationMonitoringAlertOperator;
use App\Domains\Applications\Enums\ApplicationMonitoringMetric;
use App\Domains\Applications\Events\ApplicationCrashReported;
use App\Domains\Applications\Events\ApplicationCrashUpdated;
use App\Domains\Applications\Events\ApplicationHealthMetricRecorded;
use App\Domains\Applications\Events\ApplicationMonitoringAlertTriggered;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationCrashReport;
use App\Domains\Applications\Models\ApplicationHealthMetric;
use App\Domains\Applications\Models\ApplicationMonitoringAlert;
use App\Domains\Applications\Models\ApplicationMonitoringAlertEvent;
use App\Domains\Applications\Repositories\ApplicationMonitoringRepository;
use App\Domains\Applications\Repositories\ApplicationRepository;
use App\Domains\Applications\Repositories\ApplicationVersionRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApplicationMonitoringService
{
    public function __construct(
        private readonly ApplicationMonitoringRepository $monitoringRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly ApplicationVersionRepository $versionRepository,
    ) {}

    public function resolveApplication(string $identifier): Application
    {
        return $this->applicationRepository->findByIdentifierOrFail($identifier);
    }

    /**
     * @return array<string, mixed>
     */
    public function crashDashboard(string $applicationIdentifier, ?string $from = null, ?string $to = null): array
    {
        $application = $this->resolveApplication($applicationIdentifier);
        $range = $this->resolveRange($from, $to, 7);

        return [
            'application' => $application,
            'summary' => $this->monitoringRepository->crashSummary($application->id, $range['from'], $range['to']),
            'recent' => $this->monitoringRepository->paginateCrashes($application->id, [
                'per_page' => 10,
                'sort_by' => 'occurred_at',
                'sort_dir' => 'desc',
            ])->items(),
            'chart' => $this->buildCrashChart($application->id, $range['from'], $range['to']),
            'from' => $range['from']->toIso8601String(),
            'to' => $range['to']->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthDashboard(string $applicationIdentifier, ?string $from = null, ?string $to = null): array
    {
        $application = $this->resolveApplication($applicationIdentifier);
        $range = $this->resolveRange($from, $to, 14);
        $latest = $this->monitoringRepository->latestHealthMetric($application->id);
        $series = $this->monitoringRepository->healthChartSeries($application->id, $range['from'], $range['to']);

        if (! $latest) {
            $computed = $this->computeHealthSnapshot($application->id, $range['from'], $range['to']);
            $latest = new ApplicationHealthMetric(array_merge($computed, [
                'application_id' => $application->id,
                'recorded_at' => now(),
            ]));
        }

        return [
            'application' => $application,
            'latest' => $latest,
            'metrics' => $this->monitoringRepository->recentHealthMetrics($application->id, 20),
            'chart' => [
                'labels' => $series->map(fn (ApplicationHealthMetric $m) => optional($m->recorded_at)->toDateString())->values()->all(),
                'health_score' => $series->pluck('health_score')->values()->all(),
                'crash_rate' => $series->pluck('crash_rate')->values()->all(),
                'anr_rate' => $series->pluck('anr_rate')->values()->all(),
                'api_error_rate' => $series->pluck('api_error_rate')->values()->all(),
                'avg_response_time_ms' => $series->pluck('avg_response_time_ms')->values()->all(),
                'avg_memory_usage_mb' => $series->pluck('avg_memory_usage_mb')->values()->all(),
                'avg_battery_usage' => $series->pluck('avg_battery_usage')->values()->all(),
            ],
            'from' => $range['from']->toIso8601String(),
            'to' => $range['to']->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function charts(string $applicationIdentifier, ?string $metric = null, ?string $from = null, ?string $to = null): array
    {
        $application = $this->resolveApplication($applicationIdentifier);
        $range = $this->resolveRange($from, $to, 14);
        $metricKey = $metric ?: 'health_score';

        $crashChart = $this->buildCrashChart($application->id, $range['from'], $range['to']);
        $healthSeries = $this->monitoringRepository->healthChartSeries($application->id, $range['from'], $range['to']);

        $healthValues = match ($metricKey) {
            'crash_rate' => $healthSeries->pluck('crash_rate')->values()->all(),
            'anr_rate' => $healthSeries->pluck('anr_rate')->values()->all(),
            'api_error_rate' => $healthSeries->pluck('api_error_rate')->values()->all(),
            'response_time' => $healthSeries->pluck('avg_response_time_ms')->values()->all(),
            'memory' => $healthSeries->pluck('avg_memory_usage_mb')->values()->all(),
            'battery' => $healthSeries->pluck('avg_battery_usage')->values()->all(),
            default => $healthSeries->pluck('health_score')->values()->all(),
        };

        return [
            'application' => $application,
            'metric' => $metricKey,
            'crash_chart' => $crashChart,
            'health_chart' => [
                'labels' => $healthSeries->map(fn (ApplicationHealthMetric $m) => optional($m->recorded_at)->toDateTimeString())->values()->all(),
                'values' => $healthValues,
            ],
            'from' => $range['from']->toIso8601String(),
            'to' => $range['to']->toIso8601String(),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function deviceStatistics(string $applicationIdentifier, int $limit = 20): Collection
    {
        $application = $this->resolveApplication($applicationIdentifier);

        return $this->monitoringRepository->deviceStatistics($application->id, $limit);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listCrashes(string $applicationIdentifier, array $filters = []): LengthAwarePaginator
    {
        $application = $this->resolveApplication($applicationIdentifier);

        return $this->monitoringRepository->paginateCrashes($application->id, $filters);
    }

    public function findCrash(string $applicationIdentifier, string $crashIdentifier): ApplicationCrashReport
    {
        $application = $this->resolveApplication($applicationIdentifier);

        return $this->monitoringRepository->findCrashForApplication($application->id, $crashIdentifier)
            ->load(['version:id,uuid,version_number,status,build_number', 'creator:id,uuid,full_name,email', 'updater:id,uuid,full_name,email']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCrash(string $applicationIdentifier, array $data, ?User $actor = null, bool $fromIngest = false): ApplicationCrashReport
    {
        return DB::transaction(function () use ($applicationIdentifier, $data, $actor, $fromIngest): ApplicationCrashReport {
            $application = $this->resolveApplication($applicationIdentifier);
            $crash = $this->persistCrash($application, $data, $actor);

            event(new ApplicationCrashReported($crash, $actor, $fromIngest));
            $this->evaluateAlerts($application);

            return $this->findCrash($applicationIdentifier, $crash->uuid);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCrash(string $applicationIdentifier, string $crashIdentifier, array $data, User $actor): ApplicationCrashReport
    {
        return DB::transaction(function () use ($applicationIdentifier, $crashIdentifier, $data, $actor): ApplicationCrashReport {
            $crash = $this->findCrash($applicationIdentifier, $crashIdentifier);

            $payload = ['updated_by' => $actor->id];
            foreach (['status', 'severity', 'title', 'message', 'metadata'] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }

            if (($payload['status'] ?? null) === ApplicationCrashStatus::Resolved->value) {
                $payload['resolved_at'] = now();
            }

            $updated = $this->monitoringRepository->updateCrash($crash, $payload);
            event(new ApplicationCrashUpdated($updated, $actor));

            return $this->findCrash($applicationIdentifier, $updated->uuid);
        });
    }

    public function deleteCrash(string $applicationIdentifier, string $crashIdentifier, User $actor): void
    {
        $crash = $this->findCrash($applicationIdentifier, $crashIdentifier);
        $crash->update(['updated_by' => $actor->id]);
        $crash->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function ingestCrash(string $applicationIdentifier, array $data, ?User $actor = null): ApplicationCrashReport
    {
        $data['type'] = $data['type'] ?? ApplicationCrashType::Crash->value;

        return $this->createCrash($applicationIdentifier, $data, $actor, true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function ingestApiError(string $applicationIdentifier, array $data, ?User $actor = null): ApplicationCrashReport
    {
        $data['type'] = ApplicationCrashType::ApiError->value;
        $data['title'] = $data['title'] ?? ('API Error '.($data['endpoint'] ?? 'unknown'));

        return $this->createCrash($applicationIdentifier, $data, $actor, true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function ingestHealth(string $applicationIdentifier, array $data, ?User $actor = null): ApplicationHealthMetric
    {
        return DB::transaction(function () use ($applicationIdentifier, $data, $actor): ApplicationHealthMetric {
            $application = $this->resolveApplication($applicationIdentifier);
            $versionId = null;
            $versionLabel = $data['version_label'] ?? null;

            if (! empty($data['application_version_id'])) {
                $version = $this->versionRepository->findForApplication($application->id, (string) $data['application_version_id']);
                $versionId = $version->id;
                $versionLabel = $version->version_number;
            }

            $computed = $this->computeHealthSnapshot(
                $application->id,
                now()->subDay(),
                now()
            );

            $metric = $this->monitoringRepository->createHealthMetric([
                'application_id' => $application->id,
                'application_version_id' => $versionId,
                'version_label' => $versionLabel,
                'recorded_at' => $data['recorded_at'] ?? now(),
                'health_score' => $data['health_score'] ?? $computed['health_score'],
                'crash_rate' => $data['crash_rate'] ?? $computed['crash_rate'],
                'anr_rate' => $data['anr_rate'] ?? $computed['anr_rate'],
                'api_error_rate' => $data['api_error_rate'] ?? $computed['api_error_rate'],
                'avg_response_time_ms' => $data['avg_response_time_ms'] ?? $computed['avg_response_time_ms'],
                'avg_memory_usage_mb' => $data['avg_memory_usage_mb'] ?? $computed['avg_memory_usage_mb'],
                'avg_battery_usage' => $data['avg_battery_usage'] ?? $computed['avg_battery_usage'],
                'crash_count' => $data['crash_count'] ?? $computed['crash_count'],
                'anr_count' => $data['anr_count'] ?? $computed['anr_count'],
                'api_error_count' => $data['api_error_count'] ?? $computed['api_error_count'],
                'sample_size' => $data['sample_size'] ?? $computed['sample_size'],
                'metadata' => $data['metadata'] ?? null,
                'created_by' => $actor?->id,
            ]);

            event(new ApplicationHealthMetricRecorded($metric, $actor));
            $this->evaluateAlerts($application, $metric);

            return $metric;
        });
    }

    public function recordComputedHealth(string $applicationIdentifier, ?User $actor = null): ApplicationHealthMetric
    {
        return $this->ingestHealth($applicationIdentifier, [], $actor);
    }

    /**
     * @return Collection<int, ApplicationMonitoringAlert>
     */
    public function listAlerts(string $applicationIdentifier): Collection
    {
        $application = $this->resolveApplication($applicationIdentifier);

        return $this->monitoringRepository->alertsForApplication($application->id);
    }

    /**
     * @return Collection<int, ApplicationMonitoringAlertEvent>
     */
    public function listAlertEvents(string $applicationIdentifier, int $limit = 25): Collection
    {
        $application = $this->resolveApplication($applicationIdentifier);

        return $this->monitoringRepository->recentAlertEvents($application->id, $limit);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAlert(string $applicationIdentifier, array $data, User $actor): ApplicationMonitoringAlert
    {
        $application = $this->resolveApplication($applicationIdentifier);

        return $this->monitoringRepository->createAlert([
            'application_id' => $application->id,
            'name' => $data['name'],
            'metric' => $data['metric'],
            'operator' => $data['operator'] ?? ApplicationMonitoringAlertOperator::Gte->value,
            'threshold' => $data['threshold'],
            'severity' => $data['severity'] ?? 'warning',
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            'cooldown_minutes' => $data['cooldown_minutes'] ?? 30,
            'message' => $data['message'] ?? null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAlert(string $applicationIdentifier, string $alertIdentifier, array $data, User $actor): ApplicationMonitoringAlert
    {
        $application = $this->resolveApplication($applicationIdentifier);
        $alert = $this->monitoringRepository->findAlertForApplication($application->id, $alertIdentifier);

        $payload = ['updated_by' => $actor->id];
        foreach (['name', 'metric', 'operator', 'threshold', 'severity', 'is_active', 'cooldown_minutes', 'message'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        return $this->monitoringRepository->updateAlert($alert, $payload);
    }

    public function deleteAlert(string $applicationIdentifier, string $alertIdentifier, User $actor): void
    {
        $application = $this->resolveApplication($applicationIdentifier);
        $alert = $this->monitoringRepository->findAlertForApplication($application->id, $alertIdentifier);
        $alert->update(['updated_by' => $actor->id]);
        $alert->delete();
    }

    public function acknowledgeAlertEvent(string $applicationIdentifier, string $eventIdentifier, User $actor): ApplicationMonitoringAlertEvent
    {
        $application = $this->resolveApplication($applicationIdentifier);
        $event = $this->monitoringRepository->findAlertEventForApplication($application->id, $eventIdentifier);

        $event->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $actor->id,
        ]);

        return $event->fresh(['alert', 'acknowledger']) ?? $event;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function persistCrash(Application $application, array $data, ?User $actor): ApplicationCrashReport
    {
        $versionId = null;
        $versionLabel = $data['version_label'] ?? null;

        if (! empty($data['application_version_id'])) {
            $version = $this->versionRepository->findForApplication($application->id, (string) $data['application_version_id']);
            $versionId = $version->id;
            $versionLabel = $version->version_number;
        }

        $type = (string) ($data['type'] ?? ApplicationCrashType::Crash->value);
        $title = (string) ($data['title'] ?? 'Untitled crash');
        $stack = (string) ($data['stack_trace'] ?? '');
        $fingerprint = $data['fingerprint'] ?? substr(sha1($type.'|'.$title.'|'.Str::limit($stack, 500, '')), 0, 40);

        $existing = $this->monitoringRepository->findOpenByFingerprint($application->id, $fingerprint);
        if ($existing) {
            return $this->monitoringRepository->updateCrash($existing, [
                'occurrence_count' => $existing->occurrence_count + 1,
                'occurred_at' => $data['occurred_at'] ?? now(),
                'crash_log' => $data['crash_log'] ?? $existing->crash_log,
                'stack_trace' => $data['stack_trace'] ?? $existing->stack_trace,
                'memory_usage_mb' => $data['memory_usage_mb'] ?? $existing->memory_usage_mb,
                'battery_level' => $data['battery_level'] ?? $existing->battery_level,
                'response_time_ms' => $data['response_time_ms'] ?? $existing->response_time_ms,
                'updated_by' => $actor?->id,
            ]);
        }

        return $this->monitoringRepository->createCrash([
            'application_id' => $application->id,
            'application_version_id' => $versionId,
            'version_label' => $versionLabel,
            'type' => $type,
            'severity' => $data['severity'] ?? ApplicationCrashSeverity::Error->value,
            'status' => ApplicationCrashStatus::Open->value,
            'title' => $title,
            'message' => $data['message'] ?? null,
            'stack_trace' => $data['stack_trace'] ?? null,
            'crash_log' => $data['crash_log'] ?? null,
            'fingerprint' => $fingerprint,
            'occurrence_count' => 1,
            'device_id' => $data['device_id'] ?? null,
            'device_model' => $data['device_model'] ?? null,
            'device_manufacturer' => $data['device_manufacturer'] ?? null,
            'device_os' => $data['device_os'] ?? null,
            'device_os_version' => $data['device_os_version'] ?? null,
            'device_meta' => $data['device_meta'] ?? null,
            'endpoint' => $data['endpoint'] ?? null,
            'http_status' => $data['http_status'] ?? null,
            'response_time_ms' => $data['response_time_ms'] ?? null,
            'memory_usage_mb' => $data['memory_usage_mb'] ?? null,
            'battery_level' => $data['battery_level'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? now(),
            'metadata' => $data['metadata'] ?? null,
            'created_by' => $actor?->id,
            'updated_by' => $actor?->id,
        ]);
    }

    /**
     * @return array<string, float|int>
     */
    protected function computeHealthSnapshot(int $applicationId, Carbon $from, Carbon $to): array
    {
        $rates = $this->monitoringRepository->aggregateRates($applicationId, $from, $to);

        $score = 100;
        $score -= min(40, (float) $rates['crash_rate'] * 0.8);
        $score -= min(25, (float) $rates['anr_rate'] * 0.6);
        $score -= min(20, (float) $rates['api_error_rate'] * 0.4);
        $score -= min(10, max(0, ((float) $rates['avg_response_time_ms'] - 500) / 200));
        $score = (int) max(0, min(100, round($score)));

        return array_merge($rates, ['health_score' => $score]);
    }

    protected function evaluateAlerts(Application $application, ?ApplicationHealthMetric $metric = null): void
    {
        $metric ??= $this->monitoringRepository->latestHealthMetric($application->id);
        $snapshot = $metric
            ? [
                'crash_rate' => (float) $metric->crash_rate,
                'anr_rate' => (float) $metric->anr_rate,
                'api_error_rate' => (float) $metric->api_error_rate,
                'response_time' => (float) $metric->avg_response_time_ms,
                'memory' => (float) $metric->avg_memory_usage_mb,
                'battery' => (float) $metric->avg_battery_usage,
                'health_score' => (float) $metric->health_score,
            ]
            : $this->metricValuesFromComputed($application->id);

        foreach ($this->monitoringRepository->activeAlerts($application->id) as $alert) {
            if ($alert->last_triggered_at && $alert->last_triggered_at->gt(now()->subMinutes(max(1, (int) $alert->cooldown_minutes)))) {
                continue;
            }

            $metricKey = $alert->metric instanceof ApplicationMonitoringMetric
                ? $alert->metric->value
                : (string) $alert->metric;
            $observed = $snapshot[$metricKey] ?? null;
            if ($observed === null) {
                continue;
            }

            if (! $this->compare((float) $observed, $alert->operator, (float) $alert->threshold)) {
                continue;
            }

            $event = $this->monitoringRepository->createAlertEvent([
                'application_id' => $application->id,
                'alert_id' => $alert->id,
                'metric' => $metricKey,
                'threshold' => $alert->threshold,
                'observed_value' => $observed,
                'severity' => $alert->severity?->value ?? $alert->severity,
                'status' => 'open',
                'message' => $alert->message ?: "{$alert->name} triggered ({$observed})",
                'context' => $snapshot,
                'triggered_at' => now(),
            ]);

            $this->monitoringRepository->updateAlert($alert, ['last_triggered_at' => now()]);
            event(new ApplicationMonitoringAlertTriggered($event, $alert));
        }
    }

    /**
     * @return array<string, float>
     */
    protected function metricValuesFromComputed(int $applicationId): array
    {
        $computed = $this->computeHealthSnapshot($applicationId, now()->subDay(), now());

        return [
            'crash_rate' => (float) $computed['crash_rate'],
            'anr_rate' => (float) $computed['anr_rate'],
            'api_error_rate' => (float) $computed['api_error_rate'],
            'response_time' => (float) $computed['avg_response_time_ms'],
            'memory' => (float) $computed['avg_memory_usage_mb'],
            'battery' => (float) $computed['avg_battery_usage'],
            'health_score' => (float) $computed['health_score'],
        ];
    }

    protected function compare(float $observed, ApplicationMonitoringAlertOperator|string $operator, float $threshold): bool
    {
        $op = $operator instanceof ApplicationMonitoringAlertOperator ? $operator : ApplicationMonitoringAlertOperator::from((string) $operator);

        return match ($op) {
            ApplicationMonitoringAlertOperator::Gt => $observed > $threshold,
            ApplicationMonitoringAlertOperator::Gte => $observed >= $threshold,
            ApplicationMonitoringAlertOperator::Lt => $observed < $threshold,
            ApplicationMonitoringAlertOperator::Lte => $observed <= $threshold,
            ApplicationMonitoringAlertOperator::Eq => abs($observed - $threshold) < 0.0001,
        };
    }

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    protected function resolveRange(?string $from, ?string $to, int $defaultDays): array
    {
        $end = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();
        $start = $from ? Carbon::parse($from)->startOfDay() : (clone $end)->subDays($defaultDays)->startOfDay();

        if ($end->lt($start)) {
            throw new ApiException('End date must be after start date.', 422);
        }

        return ['from' => $start, 'to' => $end];
    }

    /**
     * @return array{labels: list<string>, series: array<string, list<int>>}
     */
    protected function buildCrashChart(int $applicationId, Carbon $from, Carbon $to): array
    {
        $rows = $this->monitoringRepository->crashesByDay($applicationId, $from, $to);
        $days = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $days[] = $cursor->toDateString();
            $cursor->addDay();
        }

        $series = [
            'crash' => array_fill(0, count($days), 0),
            'anr' => array_fill(0, count($days), 0),
            'api_error' => array_fill(0, count($days), 0),
        ];

        $index = array_flip($days);
        foreach ($rows as $row) {
            $day = (string) $row->day;
            $rawType = $row->type;
            $type = $rawType instanceof \BackedEnum ? $rawType->value : (string) $rawType;
            if (! isset($index[$day], $series[$type])) {
                continue;
            }
            $series[$type][$index[$day]] = (int) $row->total;
        }

        return [
            'labels' => $days,
            'series' => $series,
        ];
    }
}

<?php

namespace App\Shared\Services\Monitoring;

use App\Domains\Monitoring\Models\MonitoringSnapshot;
use App\Domains\Monitoring\Repositories\MonitoringAlertRepository;
use App\Domains\Monitoring\Repositories\MonitoringSnapshotRepository;
use App\Domains\Monitoring\Services\EnterpriseHealthProbeService;
use Illuminate\Support\Str;

/**
 * Orchestrates Integration Hub health snapshots and alert evaluation.
 */
class HealthMonitor
{
    public function __construct(
        private readonly MetricsAggregator $aggregator,
        private readonly AlertEvaluator $alertEvaluator,
        private readonly MonitoringSnapshotRepository $snapshotRepository,
        private readonly MonitoringAlertRepository $alertRepository,
        private readonly EnterpriseHealthProbeService $probeService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function inspect(?int $companyId = null, ?int $integrationId = null): array
    {
        return $this->aggregator->aggregate($companyId, $integrationId);
    }

    /**
     * @return array{
     *     snapshot: MonitoringSnapshot,
     *     events: list<\App\Domains\Monitoring\Models\MonitoringAlertEvent>,
     *     metrics: array<string, mixed>,
     *     probes: array<string, mixed>
     * }
     */
    public function captureSnapshot(?int $companyId = null, ?int $integrationId = null): array
    {
        $metrics = $this->aggregator->aggregate($companyId, $integrationId);

        $snapshot = $this->snapshotRepository->createSnapshot([
            'uuid' => (string) Str::uuid(),
            'company_id' => $companyId,
            'integration_id' => $integrationId,
            'scope' => $integrationId ? 'integration' : 'hub',
            'health_score' => $metrics['health_score'],
            'performance_score' => $metrics['performance_score'],
            'uptime_percent' => $metrics['uptime_percent'],
            'downtime_percent' => $metrics['downtime_percent'],
            'error_rate' => $metrics['error_rate'],
            'avg_response_ms' => $metrics['avg_response_ms'],
            'webhook_success_rate' => $metrics['webhook_success_rate'],
            'queue_health_score' => $metrics['queue_health_score'],
            'availability_status' => $metrics['availability_status'],
            'authentication_status' => $metrics['authentication_status'],
            'rate_limit_status' => $metrics['rate_limit_status'],
            'server_status' => $metrics['server_status'],
            'metrics' => $metrics,
            'period_start' => $metrics['period_start'],
            'period_end' => $metrics['period_end'],
        ]);

        $alerts = $this->alertRepository->enabledForCompany($companyId);
        $events = $this->alertEvaluator->evaluate($alerts, $metrics);

        $probes = $this->probeService->probe($companyId, $snapshot, $metrics);

        if ($events !== []) {
            $this->probeService->logIncidents($companyId, $events);
        }

        return [
            'snapshot' => $snapshot,
            'events' => $events,
            'metrics' => $metrics,
            'probes' => [
                'checks_count' => count($probes['checks']),
                'services_count' => count($probes['services']),
                'logs_count' => count($probes['logs']),
            ],
        ];
    }
}

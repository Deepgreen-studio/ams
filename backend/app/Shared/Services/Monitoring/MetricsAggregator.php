<?php

namespace App\Shared\Services\Monitoring;

use App\Domains\Audit\Models\ApiLog;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Integrations\Models\IntegrationConnectionLog;
use App\Domains\Integrations\Models\WebhookLog;
use App\Domains\Queue\Models\QueueJobTrack;
use App\Shared\Services\Queue\QueueManager;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MetricsAggregator
{
    public function __construct(
        private readonly ScoreCalculator $scoreCalculator,
        private readonly QueueManager $queueManager,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function aggregate(?int $companyId = null, ?int $integrationId = null, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $from = $from ?: now()->subDay();
        $to = $to ?: now();

        $api = $this->apiMetrics($companyId, $integrationId, $from, $to);
        $auth = $this->authMetrics($companyId, $integrationId, $from, $to);
        $webhooks = $this->webhookMetrics($companyId, $from, $to);
        $queue = $this->queueMetrics($from, $to);
        $rateLimits = $this->rateLimitMetrics($companyId, $integrationId, $from, $to);
        $availability = $this->availabilityMetrics($companyId, $integrationId, $from, $to);

        $errorRate = (float) ($api['error_rate'] ?? 0);
        $uptime = (float) ($availability['uptime_percent'] ?? 0);
        $metrics = [
            'avg_response_ms' => (int) ($api['avg_response_ms'] ?? 0),
            'p95_response_ms' => (int) ($api['p95_response_ms'] ?? 0),
            'total_requests' => (int) ($api['total'] ?? 0),
            'failed_requests' => (int) ($api['failed'] ?? 0),
            'error_rate' => $errorRate,
            'availability_rate' => $uptime,
            'authentication_success_rate' => (float) ($auth['success_rate'] ?? 0),
            'webhook_success_rate' => (float) ($webhooks['success_rate'] ?? 0),
            'queue_health_score' => (float) ($queue['health_score'] ?? 0),
            'rate_limit_hit_rate' => (float) ($rateLimits['hit_rate'] ?? 0),
            'integrations_total' => (int) ($availability['integrations_total'] ?? 0),
            'integrations_healthy' => (int) ($availability['integrations_healthy'] ?? 0),
        ];

        $healthScore = $this->scoreCalculator->healthScore($metrics);
        $performanceScore = $this->scoreCalculator->performanceScore($metrics);

        return [
            'period_start' => $from->toIso8601String(),
            'period_end' => $to->toIso8601String(),
            'health_score' => $healthScore,
            'performance_score' => $performanceScore,
            'uptime_percent' => round($uptime, 2),
            'downtime_percent' => round(max(0, 100 - $uptime), 2),
            'error_rate' => round($errorRate, 2),
            'avg_response_ms' => $metrics['avg_response_ms'],
            'webhook_success_rate' => round((float) $metrics['webhook_success_rate'], 2),
            'queue_health_score' => (int) $metrics['queue_health_score'],
            'availability_status' => $this->scoreCalculator->statusFromRate($uptime),
            'authentication_status' => $this->scoreCalculator->statusFromRate((float) $metrics['authentication_success_rate']),
            'rate_limit_status' => $this->rateLimitStatus((float) $metrics['rate_limit_hit_rate']),
            'server_status' => $this->serverStatusFromIntegrations(
                (int) $metrics['integrations_total'],
                (int) $metrics['integrations_healthy']
            ),
            'api' => $api,
            'authentication' => $auth,
            'webhooks' => $webhooks,
            'queue' => $queue,
            'rate_limits' => $rateLimits,
            'availability' => $availability,
            'metrics' => $metrics,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function responseHistory(?int $companyId = null, ?int $integrationId = null, int $hours = 24): array
    {
        $from = now()->subHours($hours);
        $driver = DB::connection()->getDriverName();
        $bucket = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d %H:00:00', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')";

        $query = IntegrationConnectionLog::query()
            ->selectRaw("{$bucket} as bucket")
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful')
            ->selectRaw('AVG(duration_ms) as avg_response_ms')
            ->where('created_at', '>=', $from)
            ->groupBy('bucket')
            ->orderBy('bucket');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        if ($integrationId) {
            $query->where('integration_id', $integrationId);
        }

        return $query->get()->map(fn ($row) => [
            'bucket' => $row->bucket,
            'total' => (int) $row->total,
            'successful' => (int) $row->successful,
            'failed' => (int) $row->total - (int) $row->successful,
            'avg_response_ms' => (int) round((float) $row->avg_response_ms),
            'error_rate' => $row->total > 0
                ? round((($row->total - $row->successful) / $row->total) * 100, 2)
                : 0.0,
        ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function apiMetrics(?int $companyId, ?int $integrationId, CarbonInterface $from, CarbonInterface $to): array
    {
        $query = IntegrationConnectionLog::query()
            ->whereBetween('created_at', [$from, $to]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        if ($integrationId) {
            $query->where('integration_id', $integrationId);
        }

        $total = (clone $query)->count();
        $failed = (clone $query)->where('success', false)->count();
        $avg = (int) round((float) ((clone $query)->avg('duration_ms') ?? 0));

        $durations = (clone $query)->orderBy('duration_ms')->pluck('duration_ms')->all();
        $p95 = $this->percentile($durations, 95);

        $platform = $this->platformApiMetrics($from, $to);

        return [
            'total' => $total,
            'failed' => $failed,
            'successful' => max(0, $total - $failed),
            'error_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0.0,
            'avg_response_ms' => $avg,
            'p95_response_ms' => $p95,
            'platform' => $platform,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function platformApiMetrics(CarbonInterface $from, CarbonInterface $to): array
    {
        if (! Schema::hasTable('api_logs')) {
            return ['total' => 0, 'errors' => 0, 'avg_duration_ms' => 0, 'error_rate' => 0.0];
        }

        $query = ApiLog::query()->whereBetween('created_at', [$from, $to]);
        $total = (clone $query)->count();
        $errors = (clone $query)->where('response_code', '>=', 400)->count();
        $avg = (int) round((float) ((clone $query)->avg('duration') ?? 0));

        return [
            'total' => $total,
            'errors' => $errors,
            'avg_duration_ms' => $avg,
            'error_rate' => $total > 0 ? round(($errors / $total) * 100, 2) : 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function authMetrics(?int $companyId, ?int $integrationId, CarbonInterface $from, CarbonInterface $to): array
    {
        $query = IntegrationConnectionLog::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('request_type', 'authentication_test');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        if ($integrationId) {
            $query->where('integration_id', $integrationId);
        }

        $total = (clone $query)->count();
        $successful = (clone $query)->where('success', true)->count();

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => max(0, $total - $successful),
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : ($total === 0 ? 100.0 : 0.0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function webhookMetrics(?int $companyId, CarbonInterface $from, CarbonInterface $to): array
    {
        $query = WebhookLog::query()->whereBetween('created_at', [$from, $to]);
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $total = (clone $query)->count();
        $success = (clone $query)->where('status', 'success')->count();
        $failed = (clone $query)->whereIn('status', ['failed', 'retrying'])->count();

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($success / $total) * 100, 2) : 100.0,
            'avg_duration_ms' => (int) round((float) ((clone $query)->avg('duration_ms') ?? 0)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function queueMetrics(CarbonInterface $from, CarbonInterface $to): array
    {
        $pending = (int) (DB::table('jobs')->count());
        $failed = (int) (DB::table('failed_jobs')->count());
        $running = QueueJobTrack::query()->where('status', 'running')->count();
        $completed = QueueJobTrack::query()
            ->where('status', 'completed')
            ->whereBetween('finished_at', [$from, $to])
            ->count();
        $failedTracks = QueueJobTrack::query()
            ->where('status', 'failed')
            ->whereBetween('failed_at', [$from, $to])
            ->count();

        $health = $this->scoreCalculator->queueHealthScore($pending, $failed, $running);

        return [
            'pending' => $pending,
            'failed' => $failed,
            'running' => $running,
            'completed_period' => $completed,
            'failed_period' => $failedTracks,
            'health_score' => $health,
            'queue_sizes' => $this->queueManager->sizesByQueue(),
            'status' => match (true) {
                $health >= 90 => 'healthy',
                $health >= 70 => 'degraded',
                default => 'unhealthy',
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rateLimitMetrics(?int $companyId, ?int $integrationId, CarbonInterface $from, CarbonInterface $to): array
    {
        $query = IntegrationConnectionLog::query()
            ->whereBetween('created_at', [$from, $to])
            ->where(function ($builder): void {
                $builder->where('response_status', 429)
                    ->orWhere('error_message', 'like', '%rate limit%');
            });

        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        if ($integrationId) {
            $query->where('integration_id', $integrationId);
        }

        $hits = $query->count();
        $total = IntegrationConnectionLog::query()
            ->whereBetween('created_at', [$from, $to])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($integrationId, fn ($q) => $q->where('integration_id', $integrationId))
            ->count();

        return [
            'hits' => $hits,
            'total' => $total,
            'hit_rate' => $total > 0 ? round(($hits / $total) * 100, 2) : 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function availabilityMetrics(?int $companyId, ?int $integrationId, CarbonInterface $from, CarbonInterface $to): array
    {
        $integrations = Integration::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($integrationId, fn ($q) => $q->where('id', $integrationId))
            ->get(['id', 'health_status', 'status']);

        $total = $integrations->count();
        $healthy = $integrations->where('health_status', 'healthy')->count();
        $active = $integrations->where('status', 'active')->count();

        $checks = IntegrationConnectionLog::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('request_type', ['connection_test', 'authentication_test', 'request'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($integrationId, fn ($q) => $q->where('integration_id', $integrationId));

        $checkTotal = (clone $checks)->count();
        $checkSuccess = (clone $checks)->where('success', true)->count();
        $uptime = $checkTotal > 0
            ? round(($checkSuccess / $checkTotal) * 100, 2)
            : ($total > 0 ? round(($healthy / max(1, $total)) * 100, 2) : 100.0);

        return [
            'integrations_total' => $total,
            'integrations_healthy' => $healthy,
            'integrations_active' => $active,
            'check_total' => $checkTotal,
            'check_success' => $checkSuccess,
            'uptime_percent' => $uptime,
        ];
    }

    protected function rateLimitStatus(float $hitRate): string
    {
        return match (true) {
            $hitRate <= 1 => 'healthy',
            $hitRate <= 5 => 'degraded',
            $hitRate > 5 => 'unhealthy',
            default => 'unknown',
        };
    }

    protected function serverStatusFromIntegrations(int $total, int $healthy): string
    {
        if ($total === 0) {
            return 'unknown';
        }

        $rate = ($healthy / $total) * 100;

        return $this->scoreCalculator->statusFromRate($rate);
    }

    /**
     * @param  list<int|string|null>  $values
     */
    protected function percentile(array $values, float $percentile): int
    {
        $values = array_values(array_filter($values, fn ($v) => $v !== null));
        if ($values === []) {
            return 0;
        }

        sort($values, SORT_NUMERIC);
        $index = (int) ceil(($percentile / 100) * count($values)) - 1;
        $index = max(0, min(count($values) - 1, $index));

        return (int) $values[$index];
    }
}

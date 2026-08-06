<?php

namespace App\Shared\Services\Monitoring;

use App\Domains\Monitoring\Models\MonitoringAlert;
use App\Domains\Monitoring\Models\MonitoringAlertEvent;
use Illuminate\Support\Collection;

class AlertEvaluator
{
    /**
     * @param  Collection<int, MonitoringAlert>  $alerts
     * @param  array<string, mixed>  $metrics
     * @return list<MonitoringAlertEvent>
     */
    public function evaluate(Collection $alerts, array $metrics): array
    {
        $events = [];

        foreach ($alerts as $alert) {
            if (! $alert->is_enabled) {
                continue;
            }

            if ($alert->last_triggered_at && $alert->last_triggered_at->gt(now()->subMinutes((int) $alert->cooldown_minutes))) {
                continue;
            }

            $metric = $alert->metric?->value ?? (string) $alert->metric;
            $value = $this->metricValue($metric, $metrics);
            if ($value === null) {
                continue;
            }

            if (! $this->matches((float) $value, (string) $alert->operator, (float) $alert->threshold)) {
                continue;
            }

            $events[] = $this->createEvent($alert, (float) $value, $metrics);
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    protected function createEvent(MonitoringAlert $alert, float $value, array $metrics): MonitoringAlertEvent
    {
            $metric = $alert->metric?->value ?? (string) $alert->metric;
            $event = MonitoringAlertEvent::query()->create([
            'monitoring_alert_id' => $alert->id,
            'severity' => $this->severityFor($metric, $value),
            'status' => 'open',
            'metric_value' => $value,
            'message' => sprintf(
                'Alert "%s" triggered: %s %s %s (current: %s).',
                $alert->name,
                $metric,
                $alert->operator,
                $alert->threshold,
                $value
            ),
            'context' => [
                'metric' => $metric,
                'threshold' => $alert->threshold,
                'operator' => $alert->operator,
                'snapshot' => [
                    'health_score' => $metrics['health_score'] ?? null,
                    'error_rate' => $metrics['error_rate'] ?? null,
                    'avg_response_ms' => $metrics['avg_response_ms'] ?? null,
                ],
            ],
        ]);

        $alert->forceFill(['last_triggered_at' => now()])->save();

        return $event;
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    protected function metricValue(string $metric, array $metrics): ?float
    {
        $map = [
            'health_score' => $metrics['health_score'] ?? null,
            'performance_score' => $metrics['performance_score'] ?? null,
            'error_rate' => $metrics['error_rate'] ?? null,
            'avg_response_ms' => $metrics['avg_response_ms'] ?? null,
            'uptime_percent' => $metrics['uptime_percent'] ?? null,
            'downtime_percent' => $metrics['downtime_percent'] ?? null,
            'webhook_success_rate' => $metrics['webhook_success_rate'] ?? null,
            'queue_health_score' => $metrics['queue_health_score'] ?? null,
            'queue_failed' => data_get($metrics, 'queue.failed'),
        ];

        $value = $map[$metric] ?? null;

        return $value === null ? null : (float) $value;
    }

    protected function matches(float $value, string $operator, float $threshold): bool
    {
        return match ($operator) {
            'gt' => $value > $threshold,
            'gte' => $value >= $threshold,
            'lt' => $value < $threshold,
            'lte' => $value <= $threshold,
            'eq' => abs($value - $threshold) < 0.00001,
            default => false,
        };
    }

    protected function severityFor(string $metric, float $value): string
    {
        if (in_array($metric, ['error_rate', 'downtime_percent', 'avg_response_ms', 'queue_failed'], true)) {
            return $value >= 50 ? 'critical' : 'warning';
        }

        return $value <= 50 ? 'critical' : 'warning';
    }
}

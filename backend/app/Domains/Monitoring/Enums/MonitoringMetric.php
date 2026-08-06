<?php

namespace App\Domains\Monitoring\Enums;

enum MonitoringMetric: string
{
    case HealthScore = 'health_score';
    case PerformanceScore = 'performance_score';
    case ErrorRate = 'error_rate';
    case AvgResponseMs = 'avg_response_ms';
    case UptimePercent = 'uptime_percent';
    case DowntimePercent = 'downtime_percent';
    case WebhookSuccessRate = 'webhook_success_rate';
    case QueueHealthScore = 'queue_health_score';
    case QueueFailed = 'queue_failed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

namespace App\Domains\Applications\Enums;

enum ApplicationMonitoringMetric: string
{
    case CrashRate = 'crash_rate';
    case AnrRate = 'anr_rate';
    case ApiErrorRate = 'api_error_rate';
    case ResponseTime = 'response_time';
    case Memory = 'memory';
    case Battery = 'battery';
    case HealthScore = 'health_score';

    public function label(): string
    {
        return match ($this) {
            self::CrashRate => 'Crash Rate',
            self::AnrRate => 'ANR Rate',
            self::ApiErrorRate => 'API Error Rate',
            self::ResponseTime => 'Response Time',
            self::Memory => 'Memory Usage',
            self::Battery => 'Battery Usage',
            self::HealthScore => 'Health Score',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

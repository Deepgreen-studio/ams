<?php

namespace App\Domains\Support\Enums;

enum SupportSlaEscalationTrigger: string
{
    case ResponseAtRisk = 'response_at_risk';
    case ResponseBreached = 'response_breached';
    case ResolutionAtRisk = 'resolution_at_risk';
    case ResolutionBreached = 'resolution_breached';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::ResponseAtRisk => 'Response At Risk',
            self::ResponseBreached => 'Response Breached',
            self::ResolutionAtRisk => 'Resolution At Risk',
            self::ResolutionBreached => 'Resolution Breached',
        };
    }

    public function metric(): SupportSlaMetric
    {
        return match ($this) {
            self::ResponseAtRisk, self::ResponseBreached => SupportSlaMetric::Response,
            self::ResolutionAtRisk, self::ResolutionBreached => SupportSlaMetric::Resolution,
        };
    }
}

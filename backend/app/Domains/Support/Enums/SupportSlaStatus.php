<?php

namespace App\Domains\Support\Enums;

enum SupportSlaStatus: string
{
    case OnTrack = 'on_track';
    case AtRisk = 'at_risk';
    case Breached = 'breached';
    case Paused = 'paused';
    case Met = 'met';
    case NotApplicable = 'not_applicable';

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
            self::OnTrack => 'On Track',
            self::AtRisk => 'At Risk',
            self::Breached => 'Breached',
            self::Paused => 'Paused',
            self::Met => 'Met',
            self::NotApplicable => 'N/A',
        };
    }
}

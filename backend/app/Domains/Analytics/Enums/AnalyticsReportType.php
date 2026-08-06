<?php

namespace App\Domains\Analytics\Enums;

enum AnalyticsReportType: string
{
    case Tabular = 'tabular';
    case Chart = 'chart';
    case Grouped = 'grouped';
    case Custom = 'custom';
    case Scheduled = 'scheduled';

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
            self::Tabular => 'Tabular Report',
            self::Chart => 'Chart Report',
            self::Grouped => 'Grouped Report',
            self::Custom => 'Custom Report',
            self::Scheduled => 'Scheduled Report',
        };
    }
}

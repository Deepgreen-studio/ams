<?php

namespace App\Domains\Analytics\Enums;

enum AnalyticsReportVisibility: string
{
    case Personal = 'personal';
    case Company = 'company';
    case Shared = 'shared';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

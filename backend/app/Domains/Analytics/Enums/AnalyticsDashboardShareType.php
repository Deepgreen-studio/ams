<?php

namespace App\Domains\Analytics\Enums;

enum AnalyticsDashboardShareType: string
{
    case User = 'user';
    case Role = 'role';
    case Company = 'company';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

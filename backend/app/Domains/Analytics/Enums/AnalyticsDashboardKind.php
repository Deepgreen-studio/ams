<?php

namespace App\Domains\Analytics\Enums;

enum AnalyticsDashboardKind: string
{
    case Dashboard = 'dashboard';
    case SavedView = 'saved_view';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

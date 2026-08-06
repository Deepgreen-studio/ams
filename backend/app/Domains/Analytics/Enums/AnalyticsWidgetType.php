<?php

namespace App\Domains\Analytics\Enums;

enum AnalyticsWidgetType: string
{
    case Kpi = 'kpi';
    case LineChart = 'line_chart';
    case BarChart = 'bar_chart';
    case PieChart = 'pie_chart';
    case Table = 'table';
    case Heatmap = 'heatmap';
    case Gauge = 'gauge';
    case Map = 'map';
    case ActivityFeed = 'activity_feed';
    case Notifications = 'notifications';

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
            self::Kpi => 'KPI Card',
            self::LineChart => 'Line Chart',
            self::BarChart => 'Bar Chart',
            self::PieChart => 'Pie Chart',
            self::Table => 'Table',
            self::Heatmap => 'Heatmap',
            self::Gauge => 'Gauge',
            self::Map => 'Map',
            self::ActivityFeed => 'Activity Feed',
            self::Notifications => 'Notifications',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::Kpi, self::Gauge => 'kpi',
            self::LineChart, self::BarChart, self::PieChart, self::Heatmap => 'charts',
            self::Table => 'tables',
            self::Map => 'maps',
            self::ActivityFeed => 'activity',
            self::Notifications => 'notifications',
        };
    }

    public function defaultWidth(): int
    {
        return match ($this) {
            self::Kpi, self::Gauge => 3,
            self::Table, self::ActivityFeed, self::Notifications => 6,
            self::Map, self::Heatmap => 6,
            default => 6,
        };
    }

    public function defaultHeight(): int
    {
        return match ($this) {
            self::Kpi, self::Gauge => 2,
            self::Table => 4,
            self::ActivityFeed, self::Notifications => 4,
            self::Map => 4,
            default => 3,
        };
    }
}

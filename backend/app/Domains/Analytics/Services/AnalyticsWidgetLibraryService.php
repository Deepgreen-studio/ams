<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Enums\AnalyticsWidgetType;

class AnalyticsWidgetLibraryService
{
    /**
     * Catalog of designer-ready widgets.
     *
     * @return array{
     *     groups: list<array{key: string, label: string}>,
     *     widgets: list<array<string, mixed>>
     * }
     */
    public function catalog(): array
    {
        $groups = [
            ['key' => 'kpi', 'label' => 'KPI Cards'],
            ['key' => 'charts', 'label' => 'Charts'],
            ['key' => 'tables', 'label' => 'Tables'],
            ['key' => 'maps', 'label' => 'Maps'],
            ['key' => 'activity', 'label' => 'Activity Feed'],
            ['key' => 'notifications', 'label' => 'Notifications'],
        ];

        $widgets = [];
        foreach (AnalyticsWidgetType::cases() as $type) {
            $widgets[] = [
                'type' => $type->value,
                'label' => $type->label(),
                'group' => $type->group(),
                'description' => $this->description($type),
                'default_width' => $type->defaultWidth(),
                'default_height' => $type->defaultHeight(),
                'default_query_config' => $this->defaultQueryConfig($type),
                'default_visualization_config' => [
                    'color' => '#0f766e',
                    'show_legend' => true,
                ],
            ];
        }

        return [
            'groups' => $groups,
            'widgets' => $widgets,
            'grid' => [
                'columns' => 12,
                'row_height' => 80,
                'gap' => 16,
                'min_width' => 2,
                'min_height' => 2,
            ],
        ];
    }

    protected function description(AnalyticsWidgetType $type): string
    {
        return match ($type) {
            AnalyticsWidgetType::Kpi => 'Single metric KPI card with optional comparison.',
            AnalyticsWidgetType::Gauge => 'Progress-style gauge for a single metric.',
            AnalyticsWidgetType::LineChart => 'Time-series line chart for trends.',
            AnalyticsWidgetType::BarChart => 'Category comparison bar chart.',
            AnalyticsWidgetType::PieChart => 'Distribution pie chart.',
            AnalyticsWidgetType::Heatmap => 'Intensity heatmap across categories and time.',
            AnalyticsWidgetType::Table => 'Tabular breakdown of top metrics.',
            AnalyticsWidgetType::Map => 'Geographic distribution map markers.',
            AnalyticsWidgetType::ActivityFeed => 'Recent platform activity timeline.',
            AnalyticsWidgetType::Notifications => 'Latest notification delivery signals.',
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultQueryConfig(AnalyticsWidgetType $type): array
    {
        return match ($type) {
            AnalyticsWidgetType::ActivityFeed => ['limit' => 15, 'source' => 'activity_log'],
            AnalyticsWidgetType::Notifications => ['limit' => 15, 'source' => 'notifications'],
            AnalyticsWidgetType::Map => ['metric' => 'event_count', 'group_by' => 'region'],
            AnalyticsWidgetType::Heatmap => ['metric' => 'event_count', 'group_by' => 'day_category'],
            default => ['metric' => 'event_count'],
        };
    }
}

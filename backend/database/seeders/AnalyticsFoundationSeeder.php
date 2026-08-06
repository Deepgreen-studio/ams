<?php

namespace Database\Seeders;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsDashboardKind;
use App\Domains\Analytics\Enums\AnalyticsDashboardStatus;
use App\Domains\Analytics\Enums\AnalyticsDashboardVisibility;
use App\Domains\Analytics\Enums\AnalyticsReportStatus;
use App\Domains\Analytics\Enums\AnalyticsReportType;
use App\Domains\Analytics\Enums\AnalyticsReportVisibility;
use App\Domains\Analytics\Enums\AnalyticsWidgetType;
use App\Domains\Analytics\Models\AnalyticsDashboard;
use App\Domains\Analytics\Models\AnalyticsEvent;
use App\Domains\Analytics\Models\AnalyticsReport;
use App\Domains\Analytics\Models\AnalyticsWidget;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AnalyticsFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $overview = AnalyticsDashboard::query()->firstOrCreate(
            [
                'company_id' => null,
                'slug' => 'enterprise-overview',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Enterprise Overview',
                'description' => 'Central analytics hub across business, operational, application, customer, API, and system domains.',
                'kind' => AnalyticsDashboardKind::Dashboard->value,
                'category' => AnalyticsCategory::Business->value,
                'status' => AnalyticsDashboardStatus::Published->value,
                'visibility' => AnalyticsDashboardVisibility::System->value,
                'layout' => ['columns' => 12, 'row_height' => 80, 'gap' => 16],
                'filters' => [
                    'from' => now()->subDays(29)->toDateString(),
                    'to' => now()->toDateString(),
                ],
                'settings' => [
                    'auto_refresh_seconds' => 300,
                    'theme' => 'light',
                    'show_filters' => true,
                ],
                'is_default' => true,
                'is_system' => true,
                'is_shared' => true,
                'is_template' => false,
                'sort_order' => 0,
            ]
        );

        $overview->forceFill([
            'visibility' => AnalyticsDashboardVisibility::System->value,
            'layout' => ['columns' => 12, 'row_height' => 80, 'gap' => 16],
            'settings' => [
                'auto_refresh_seconds' => 300,
                'theme' => 'light',
                'show_filters' => true,
            ],
            'is_template' => false,
        ])->save();

        $this->seedWidgets($overview, [
            ['key' => 'total_events_kpi', 'name' => 'Total Events', 'type' => AnalyticsWidgetType::Kpi->value, 'category' => AnalyticsCategory::Business->value, 'x' => 0, 'y' => 0, 'w' => 3, 'h' => 2],
            ['key' => 'events_by_category', 'name' => 'Events by Category', 'type' => AnalyticsWidgetType::BarChart->value, 'category' => AnalyticsCategory::Business->value, 'x' => 3, 'y' => 0, 'w' => 5, 'h' => 3],
            ['key' => 'events_trend', 'name' => 'Event Trend', 'type' => AnalyticsWidgetType::LineChart->value, 'category' => AnalyticsCategory::Operational->value, 'x' => 8, 'y' => 0, 'w' => 4, 'h' => 3],
            ['key' => 'top_events_table', 'name' => 'Top Events', 'type' => AnalyticsWidgetType::Table->value, 'category' => AnalyticsCategory::System->value, 'x' => 0, 'y' => 3, 'w' => 6, 'h' => 4],
            ['key' => 'activity_feed', 'name' => 'Activity Feed', 'type' => AnalyticsWidgetType::ActivityFeed->value, 'category' => AnalyticsCategory::System->value, 'x' => 6, 'y' => 3, 'w' => 6, 'h' => 4],
        ]);

        AnalyticsDashboard::query()->updateOrCreate(
            [
                'company_id' => null,
                'slug' => 'last-7-days-operations',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Last 7 Days — Operations',
                'description' => 'Saved filter view for operational analytics over the last 7 days.',
                'kind' => AnalyticsDashboardKind::SavedView->value,
                'category' => AnalyticsCategory::Operational->value,
                'status' => AnalyticsDashboardStatus::Published->value,
                'visibility' => AnalyticsDashboardVisibility::Shared->value,
                'layout' => ['columns' => 12],
                'filters' => [
                    'from' => now()->subDays(6)->toDateString(),
                    'to' => now()->toDateString(),
                    'category' => AnalyticsCategory::Operational->value,
                ],
                'is_default' => false,
                'is_system' => true,
                'is_shared' => true,
                'is_template' => false,
                'sort_order' => 10,
            ]
        );

        $opsTemplate = AnalyticsDashboard::query()->updateOrCreate(
            [
                'company_id' => null,
                'slug' => 'template-operations-board',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Operations Board Template',
                'description' => 'Starter template with KPI, charts, notifications, and activity widgets.',
                'kind' => AnalyticsDashboardKind::Dashboard->value,
                'category' => AnalyticsCategory::Operational->value,
                'status' => AnalyticsDashboardStatus::Published->value,
                'visibility' => AnalyticsDashboardVisibility::Template->value,
                'layout' => ['columns' => 12, 'row_height' => 80, 'gap' => 16],
                'filters' => [
                    'from' => now()->subDays(29)->toDateString(),
                    'to' => now()->toDateString(),
                ],
                'settings' => ['auto_refresh_seconds' => 180, 'show_filters' => true],
                'is_default' => false,
                'is_system' => true,
                'is_shared' => true,
                'is_template' => true,
                'sort_order' => 1,
            ]
        );

        $this->seedWidgets($opsTemplate, [
            ['key' => 'ops_kpi', 'name' => 'Operational Events', 'type' => AnalyticsWidgetType::Kpi->value, 'category' => AnalyticsCategory::Operational->value, 'x' => 0, 'y' => 0, 'w' => 3, 'h' => 2],
            ['key' => 'ops_chart', 'name' => 'Ops Trend', 'type' => AnalyticsWidgetType::LineChart->value, 'category' => AnalyticsCategory::Operational->value, 'x' => 3, 'y' => 0, 'w' => 5, 'h' => 3],
            ['key' => 'ops_map', 'name' => 'Regional Map', 'type' => AnalyticsWidgetType::Map->value, 'category' => AnalyticsCategory::Operational->value, 'x' => 8, 'y' => 0, 'w' => 4, 'h' => 3],
            ['key' => 'ops_notifications', 'name' => 'Notifications', 'type' => AnalyticsWidgetType::Notifications->value, 'category' => AnalyticsCategory::Operational->value, 'x' => 0, 'y' => 3, 'w' => 6, 'h' => 4],
            ['key' => 'ops_activity', 'name' => 'Activity', 'type' => AnalyticsWidgetType::ActivityFeed->value, 'category' => AnalyticsCategory::System->value, 'x' => 6, 'y' => 3, 'w' => 6, 'h' => 4],
        ]);

        $bizTemplate = AnalyticsDashboard::query()->updateOrCreate(
            [
                'company_id' => null,
                'slug' => 'template-business-overview',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Business Overview Template',
                'description' => 'Business analytics starter with KPI cards and category charts.',
                'kind' => AnalyticsDashboardKind::Dashboard->value,
                'category' => AnalyticsCategory::Business->value,
                'status' => AnalyticsDashboardStatus::Published->value,
                'visibility' => AnalyticsDashboardVisibility::Template->value,
                'layout' => ['columns' => 12, 'row_height' => 80, 'gap' => 16],
                'filters' => [
                    'from' => now()->subDays(29)->toDateString(),
                    'to' => now()->toDateString(),
                ],
                'is_default' => false,
                'is_system' => true,
                'is_shared' => true,
                'is_template' => true,
                'sort_order' => 2,
            ]
        );

        $this->seedWidgets($bizTemplate, [
            ['key' => 'biz_kpi', 'name' => 'Business Events', 'type' => AnalyticsWidgetType::Kpi->value, 'category' => AnalyticsCategory::Business->value, 'x' => 0, 'y' => 0, 'w' => 4, 'h' => 2],
            ['key' => 'biz_bars', 'name' => 'Category Mix', 'type' => AnalyticsWidgetType::BarChart->value, 'category' => AnalyticsCategory::Business->value, 'x' => 4, 'y' => 0, 'w' => 8, 'h' => 3],
            ['key' => 'biz_table', 'name' => 'Top Events', 'type' => AnalyticsWidgetType::Table->value, 'category' => AnalyticsCategory::Business->value, 'x' => 0, 'y' => 3, 'w' => 12, 'h' => 4],
        ]);

        if (AnalyticsEvent::query()->count() === 0) {
            foreach (AnalyticsCategory::cases() as $category) {
                AnalyticsEvent::factory()
                    ->count(8)
                    ->category($category)
                    ->create([
                        'event_source' => 'analytics_foundation_seeder',
                        'occurred_at' => now()->subDays(rand(0, 14)),
                    ]);
            }
        }

        $this->seedReports();
    }

    protected function seedReports(): void
    {
        AnalyticsReport::query()->updateOrCreate(
            ['company_id' => null, 'slug' => 'events-tabular'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Events Tabular Report',
                'description' => 'Saved tabular report across analytics events.',
                'category' => AnalyticsCategory::Business->value,
                'report_type' => AnalyticsReportType::Tabular->value,
                'status' => AnalyticsReportStatus::Active->value,
                'visibility' => AnalyticsReportVisibility::Shared->value,
                'is_saved' => true,
                'is_scheduled' => false,
                'columns' => [
                    ['key' => 'occurred_at', 'label' => 'Occurred At'],
                    ['key' => 'category', 'label' => 'Category'],
                    ['key' => 'event_name', 'label' => 'Event'],
                    ['key' => 'metric_count', 'label' => 'Count'],
                ],
                'filters' => [
                    'from' => now()->subDays(29)->toDateString(),
                    'to' => now()->toDateString(),
                ],
                'sorting' => ['field' => 'occurred_at', 'direction' => 'desc'],
                'format_defaults' => ['format' => 'csv'],
            ]
        );

        AnalyticsReport::query()->updateOrCreate(
            ['company_id' => null, 'slug' => 'events-by-category'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Events by Category',
                'description' => 'Grouped report of events by analytics category.',
                'category' => AnalyticsCategory::Operational->value,
                'report_type' => AnalyticsReportType::Grouped->value,
                'status' => AnalyticsReportStatus::Active->value,
                'visibility' => AnalyticsReportVisibility::Shared->value,
                'is_saved' => true,
                'grouping' => ['fields' => ['category'], 'aggregate' => 'count'],
                'filters' => [
                    'from' => now()->subDays(29)->toDateString(),
                    'to' => now()->toDateString(),
                ],
                'format_defaults' => ['format' => 'excel'],
            ]
        );

        AnalyticsReport::query()->updateOrCreate(
            ['company_id' => null, 'slug' => 'events-chart'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Events Chart Report',
                'description' => 'Chart-oriented analytics report.',
                'category' => AnalyticsCategory::System->value,
                'report_type' => AnalyticsReportType::Chart->value,
                'status' => AnalyticsReportStatus::Active->value,
                'visibility' => AnalyticsReportVisibility::Shared->value,
                'is_saved' => true,
                'chart_config' => ['type' => 'bar'],
                'filters' => [
                    'from' => now()->subDays(14)->toDateString(),
                    'to' => now()->toDateString(),
                ],
                'format_defaults' => ['format' => 'pdf'],
            ]
        );
    }

    /**
     * @param  list<array<string, mixed>>  $widgets
     */
    protected function seedWidgets(AnalyticsDashboard $dashboard, array $widgets): void
    {
        foreach ($widgets as $index => $widget) {
            AnalyticsWidget::query()->updateOrCreate(
                [
                    'analytics_dashboard_id' => $dashboard->id,
                    'key' => $widget['key'],
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $widget['name'],
                    'type' => $widget['type'],
                    'category' => $widget['category'],
                    'data_source' => match ($widget['type']) {
                        AnalyticsWidgetType::ActivityFeed->value => 'activity_log',
                        AnalyticsWidgetType::Notifications->value => 'notifications',
                        default => 'analytics_events',
                    },
                    'query_config' => ['metric' => 'event_count', 'category' => $widget['category']],
                    'visualization_config' => ['color' => '#0f766e'],
                    'position_x' => $widget['x'],
                    'position_y' => $widget['y'],
                    'width' => $widget['w'],
                    'height' => $widget['h'],
                    'sort_order' => $index + 1,
                    'refresh_interval_seconds' => 300,
                    'is_visible' => true,
                ]
            );
        }
    }
}

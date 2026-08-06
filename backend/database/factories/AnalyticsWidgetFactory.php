<?php

namespace Database\Factories;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsWidgetType;
use App\Domains\Analytics\Models\AnalyticsDashboard;
use App\Domains\Analytics\Models\AnalyticsWidget;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AnalyticsWidget>
 */
class AnalyticsWidgetFactory extends Factory
{
    protected $model = AnalyticsWidget::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'uuid' => (string) Str::uuid(),
            'analytics_dashboard_id' => AnalyticsDashboard::factory(),
            'name' => Str::title($name),
            'key' => Str::slug($name, '_'),
            'type' => fake()->randomElement(AnalyticsWidgetType::values()),
            'category' => fake()->randomElement(AnalyticsCategory::values()),
            'data_source' => 'analytics_events',
            'query_config' => [
                'metric' => 'event_count',
                'group_by' => 'day',
            ],
            'visualization_config' => [
                'color' => '#0f766e',
            ],
            'position_x' => 0,
            'position_y' => 0,
            'width' => 4,
            'height' => 2,
            'sort_order' => 0,
            'refresh_interval_seconds' => 300,
            'is_visible' => true,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function forDashboard(AnalyticsDashboard $dashboard): static
    {
        return $this->state(fn (): array => [
            'analytics_dashboard_id' => $dashboard->id,
        ]);
    }
}

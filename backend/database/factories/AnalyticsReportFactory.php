<?php

namespace Database\Factories;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsReportStatus;
use App\Domains\Analytics\Enums\AnalyticsReportType;
use App\Domains\Analytics\Enums\AnalyticsReportVisibility;
use App\Domains\Analytics\Models\AnalyticsReport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AnalyticsReport>
 */
class AnalyticsReportFactory extends Factory
{
    protected $model = AnalyticsReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'uuid' => (string) Str::uuid(),
            'company_id' => null,
            'owner_id' => null,
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
            'category' => fake()->randomElement(AnalyticsCategory::values()),
            'report_type' => AnalyticsReportType::Tabular->value,
            'status' => AnalyticsReportStatus::Draft->value,
            'visibility' => AnalyticsReportVisibility::Personal->value,
            'is_saved' => true,
            'is_scheduled' => false,
            'query_config' => ['metric' => 'event_count'],
            'columns' => [
                ['key' => 'occurred_at', 'label' => 'Occurred At'],
                ['key' => 'category', 'label' => 'Category'],
                ['key' => 'event_name', 'label' => 'Event Name'],
                ['key' => 'metric_count', 'label' => 'Count'],
            ],
            'filters' => [
                'from' => now()->subDays(29)->toDateString(),
                'to' => now()->toDateString(),
            ],
            'sorting' => ['field' => 'occurred_at', 'direction' => 'desc'],
            'grouping' => null,
            'chart_config' => null,
            'layout' => ['density' => 'comfortable'],
            'schedule_config' => null,
            'format_defaults' => ['format' => 'csv'],
            'last_run_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function grouped(): static
    {
        return $this->state(fn (): array => [
            'report_type' => AnalyticsReportType::Grouped->value,
            'grouping' => ['fields' => ['category'], 'aggregate' => 'count'],
        ]);
    }

    public function chart(): static
    {
        return $this->state(fn (): array => [
            'report_type' => AnalyticsReportType::Chart->value,
            'chart_config' => ['type' => 'bar'],
        ]);
    }
}

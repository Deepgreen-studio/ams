<?php

namespace Database\Factories;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsDashboardKind;
use App\Domains\Analytics\Enums\AnalyticsDashboardStatus;
use App\Domains\Analytics\Enums\AnalyticsDashboardVisibility;
use App\Domains\Analytics\Models\AnalyticsDashboard;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AnalyticsDashboard>
 */
class AnalyticsDashboardFactory extends Factory
{
    protected $model = AnalyticsDashboard::class;

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
            'kind' => AnalyticsDashboardKind::Dashboard->value,
            'category' => fake()->randomElement(AnalyticsCategory::values()),
            'status' => AnalyticsDashboardStatus::Draft->value,
            'visibility' => AnalyticsDashboardVisibility::Personal->value,
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
            'is_default' => false,
            'is_system' => false,
            'is_shared' => false,
            'is_template' => false,
            'template_source_id' => null,
            'sort_order' => 0,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => AnalyticsDashboardStatus::Published->value,
        ]);
    }

    public function savedView(): static
    {
        return $this->state(fn (): array => [
            'kind' => AnalyticsDashboardKind::SavedView->value,
        ]);
    }

    public function template(): static
    {
        return $this->state(fn (): array => [
            'is_template' => true,
            'visibility' => AnalyticsDashboardVisibility::Template->value,
            'status' => AnalyticsDashboardStatus::Published->value,
        ]);
    }

    public function companyScoped(): static
    {
        return $this->state(fn (): array => [
            'visibility' => AnalyticsDashboardVisibility::Company->value,
        ]);
    }
}

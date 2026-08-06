<?php

namespace Database\Factories;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Models\AnalyticsEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AnalyticsEvent>
 */
class AnalyticsEventFactory extends Factory
{
    protected $model = AnalyticsEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = fake()->randomElement(AnalyticsCategory::values());

        return [
            'uuid' => (string) Str::uuid(),
            'company_id' => null,
            'user_id' => null,
            'application_id' => null,
            'customer_id' => null,
            'category' => $category,
            'event_name' => fake()->randomElement([
                'metric.recorded',
                'entity.created',
                'entity.updated',
                'request.completed',
                'job.completed',
                'session.started',
            ]),
            'event_source' => fake()->randomElement([
                'applications',
                'customers',
                'integrations',
                'notifications',
                'system',
            ]),
            'subject_type' => null,
            'subject_id' => null,
            'properties' => ['source' => 'factory'],
            'metrics' => [
                'count' => fake()->numberBetween(1, 100),
                'value' => fake()->randomFloat(2, 0, 1000),
            ],
            'ip_address' => fake()->optional()->ipv4(),
            'user_agent' => fake()->optional()->userAgent(),
            'occurred_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function category(AnalyticsCategory|string $category): static
    {
        return $this->state(fn (): array => [
            'category' => $category instanceof AnalyticsCategory ? $category->value : $category,
        ]);
    }
}

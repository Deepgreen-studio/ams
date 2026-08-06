<?php

namespace Database\Factories;

use App\Domains\Customers\Enums\CustomerRiskLevel;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerAnalyticsSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomerAnalyticsSnapshot>
 */
class CustomerAnalyticsSnapshotFactory extends Factory
{
    protected $model = CustomerAnalyticsSnapshot::class;

    public function definition(): array
    {
        $health = fake()->numberBetween(40, 95);

        return [
            'uuid' => (string) Str::uuid(),
            'snapshot_date' => now()->toDateString(),
            'applications_total' => fake()->numberBetween(0, 8),
            'applications_active' => fake()->numberBetween(0, 5),
            'integrations_total' => fake()->numberBetween(0, 4),
            'api_usage_count' => fake()->numberBetween(0, 5000),
            'login_activity_count' => fake()->numberBetween(0, 50),
            'support_tickets_open' => fake()->numberBetween(0, 5),
            'support_tickets_total' => fake()->numberBetween(0, 20),
            'subscription_status' => 'active',
            'subscription_active' => true,
            'health_score' => $health,
            'activity_score' => fake()->numberBetween(30, 100),
            'risk_level' => $health < 50 ? CustomerRiskLevel::High->value : CustomerRiskLevel::Low->value,
            'metrics' => ['source' => 'factory'],
            'computed_at' => now(),
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => ['customer_id' => $customer->id]);
    }
}

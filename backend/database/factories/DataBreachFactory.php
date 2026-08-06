<?php

namespace Database\Factories;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\DataBreachSeverity;
use App\Domains\Compliance\Enums\DataBreachStatus;
use App\Domains\Compliance\Enums\DataBreachType;
use App\Domains\Compliance\Models\DataBreach;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DataBreach>
 */
class DataBreachFactory extends Factory
{
    protected $model = DataBreach::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'breach_number' => sprintf('BRH-%s-%05d', now()->format('Ymd'), fake()->unique()->numberBetween(1, 99999)),
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'breach_type' => fake()->randomElement(DataBreachType::values()),
            'status' => DataBreachStatus::Reported->value,
            'severity' => DataBreachSeverity::Medium->value,
            'discovered_at' => now(),
            'occurred_at' => now()->subHours(2),
            'affected_user_count' => 0,
            'affected_users' => [],
            'affected_data_categories' => ['email', 'name'],
            'personal_data_involved' => true,
            'special_category_data' => false,
            'regulator_notification_required' => false,
            'customer_notification_required' => false,
            'assigned_to' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => [
            'company_id' => $company->id,
        ]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (): array => [
            'assigned_to' => $user->id,
        ]);
    }

    public function assessing(): static
    {
        return $this->state(fn (): array => [
            'status' => DataBreachStatus::Assessing->value,
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (): array => [
            'severity' => DataBreachSeverity::Critical->value,
            'risk_likelihood' => 5,
            'risk_impact' => 5,
            'risk_score' => 25,
            'risk_level' => DataBreachSeverity::Critical->value,
            'regulator_notification_required' => true,
            'customer_notification_required' => true,
            'regulator_deadline_at' => now()->addHours(72),
        ]);
    }
}

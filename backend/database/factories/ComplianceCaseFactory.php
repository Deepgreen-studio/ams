<?php

namespace Database\Factories;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\ComplianceCasePriority;
use App\Domains\Compliance\Enums\ComplianceCaseStatus;
use App\Domains\Compliance\Enums\ComplianceCaseType;
use App\Domains\Compliance\Models\ComplianceCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ComplianceCase>
 */
class ComplianceCaseFactory extends Factory
{
    protected $model = ComplianceCase::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'case_number' => sprintf('CMP-%s-%05d', now()->format('Ymd'), fake()->unique()->numberBetween(1, 99999)),
            'title' => fake()->sentence(6),
            'description' => fake()->paragraphs(2, true),
            'case_type' => fake()->randomElement(ComplianceCaseType::values()),
            'priority' => fake()->randomElement(ComplianceCasePriority::values()),
            'status' => ComplianceCaseStatus::Open->value,
            'assigned_to' => null,
            'due_date' => fake()->optional()->dateTimeBetween('now', '+60 days')?->format('Y-m-d'),
            'completed_at' => null,
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
            'status' => ComplianceCaseStatus::InProgress->value,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => ComplianceCaseStatus::Completed->value,
            'completed_at' => now(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\DpiaStatus;
use App\Domains\Compliance\Enums\DpiaTemplate;
use App\Domains\Compliance\Models\DpiaAssessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DpiaAssessment>
 */
class DpiaAssessmentFactory extends Factory
{
    protected $model = DpiaAssessment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'assessment_number' => sprintf('DPIA-%s-%05d', now()->format('Ymd'), fake()->unique()->numberBetween(1, 99999)),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'template_code' => DpiaTemplate::Standard->value,
            'status' => DpiaStatus::Draft->value,
            'wizard_step' => 1,
            'wizard_payload' => [],
            'processing_purpose' => fake()->sentence(),
            'data_categories' => ['email', 'name'],
            'data_subjects' => ['customers'],
            'review_due_at' => now()->addMonths(12)->toDateString(),
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

    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'status' => DpiaStatus::InProgress->value,
            'wizard_step' => 3,
        ]);
    }

    public function pendingReview(): static
    {
        return $this->state(fn (): array => [
            'status' => DpiaStatus::PendingReview->value,
            'submitted_at' => now(),
            'wizard_step' => 5,
        ]);
    }
}

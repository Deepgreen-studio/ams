<?php

namespace Database\Factories;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\RiskCategory;
use App\Domains\Compliance\Enums\RiskLevel;
use App\Domains\Compliance\Enums\RiskRegisterStatus;
use App\Domains\Compliance\Models\DpiaAssessment;
use App\Domains\Compliance\Models\RiskRegister;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RiskRegister>
 */
class RiskRegisterFactory extends Factory
{
    protected $model = RiskRegister::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $likelihood = fake()->numberBetween(1, 5);
        $impact = fake()->numberBetween(1, 5);
        $score = $likelihood * $impact;

        return [
            'uuid' => (string) Str::uuid(),
            'risk_number' => sprintf('RSK-%s-%05d', now()->format('Ymd'), fake()->unique()->numberBetween(1, 99999)),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(RiskCategory::values()),
            'status' => RiskRegisterStatus::Identified->value,
            'likelihood' => $likelihood,
            'impact' => $impact,
            'risk_score' => $score,
            'risk_level' => RiskLevel::fromRiskScore($score)->value,
            'mitigation_plan' => fake()->optional()->paragraph(),
            'review_due_at' => now()->addMonths(3)->toDateString(),
            'identified_at' => now(),
            'owner_id' => null,
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

    public function forDpia(DpiaAssessment $dpia): static
    {
        return $this->state(fn (): array => [
            'company_id' => $dpia->company_id,
            'dpia_assessment_id' => $dpia->id,
        ]);
    }

    public function ownedBy(User $user): static
    {
        return $this->state(fn (): array => [
            'owner_id' => $user->id,
        ]);
    }

    public function high(): static
    {
        return $this->state(fn (): array => [
            'likelihood' => 4,
            'impact' => 4,
            'risk_score' => 16,
            'risk_level' => RiskLevel::High->value,
            'status' => RiskRegisterStatus::Mitigating->value,
        ]);
    }
}

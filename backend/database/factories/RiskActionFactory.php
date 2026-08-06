<?php

namespace Database\Factories;

use App\Domains\Compliance\Enums\RiskActionStatus;
use App\Domains\Compliance\Enums\RiskActionType;
use App\Domains\Compliance\Models\RiskAction;
use App\Domains\Compliance\Models\RiskRegister;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RiskAction>
 */
class RiskActionFactory extends Factory
{
    protected $model = RiskAction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'action_type' => RiskActionType::Mitigation->value,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'status' => RiskActionStatus::Planned->value,
            'performed_by' => null,
            'due_at' => now()->addDays(14),
            'completed_at' => null,
            'metadata' => null,
        ];
    }

    public function forRisk(RiskRegister $risk): static
    {
        return $this->state(fn (): array => [
            'risk_register_id' => $risk->id,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => RiskActionStatus::Completed->value,
            'completed_at' => now(),
        ]);
    }
}

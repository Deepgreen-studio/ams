<?php

namespace Database\Factories;

use App\Domains\Compliance\Enums\BreachActionStatus;
use App\Domains\Compliance\Enums\BreachActionType;
use App\Domains\Compliance\Models\BreachAction;
use App\Domains\Compliance\Models\DataBreach;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BreachAction>
 */
class BreachActionFactory extends Factory
{
    protected $model = BreachAction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'action_type' => BreachActionType::Investigation->value,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'status' => BreachActionStatus::Planned->value,
            'performed_by' => null,
            'due_at' => now()->addDays(3),
            'completed_at' => null,
            'metadata' => null,
        ];
    }

    public function forBreach(DataBreach $breach): static
    {
        return $this->state(fn (): array => [
            'data_breach_id' => $breach->id,
        ]);
    }

    public function containment(): static
    {
        return $this->state(fn (): array => [
            'action_type' => BreachActionType::Containment->value,
            'title' => 'Containment action',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => BreachActionStatus::Completed->value,
            'completed_at' => now(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\ConsentChannel;
use App\Domains\Compliance\Models\ConsentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ConsentType>
 */
class ConsentTypeFactory extends Factory
{
    protected $model = ConsentType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $channel = fake()->randomElement(ConsentChannel::values());

        return [
            'uuid' => (string) Str::uuid(),
            'code' => $channel.'_'.fake()->unique()->numerify('###'),
            'name' => ConsentChannel::from($channel)->label(),
            'description' => fake()->sentence(),
            'channel' => $channel,
            'current_version' => '1.0',
            'is_required' => false,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => [
            'company_id' => $company->id,
        ]);
    }

    public function channel(ConsentChannel $channel): static
    {
        return $this->state(fn (): array => [
            'code' => $channel->value,
            'name' => $channel->label(),
            'channel' => $channel->value,
        ]);
    }
}

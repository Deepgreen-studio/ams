<?php

namespace Database\Factories;

use App\Domains\Applications\Enums\ApplicationCategory;
use App\Domains\Applications\Enums\ApplicationPlatform;
use App\Domains\Applications\Enums\ApplicationStatus;
use App\Domains\Applications\Enums\ApplicationVisibility;
use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'uuid' => (string) Str::uuid(),
            'integration_id' => null,
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(12),
            'platform' => fake()->randomElement(ApplicationPlatform::values()),
            'category' => fake()->randomElement(ApplicationCategory::values()),
            'icon' => null,
            'banner' => null,
            'current_version' => fake()->numerify('#.#.#'),
            'minimum_supported_version' => fake()->numerify('#.#.#'),
            'status' => ApplicationStatus::Draft->value,
            'visibility' => ApplicationVisibility::Private->value,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => ApplicationStatus::Active->value,
        ]);
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => [
            'company_id' => $company->id,
        ]);
    }
}

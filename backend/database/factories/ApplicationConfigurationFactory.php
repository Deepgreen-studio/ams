<?php

namespace Database\Factories;

use App\Domains\Applications\Enums\ApplicationConfigurationStatus;
use App\Domains\Applications\Enums\ApplicationConfigurationType;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApplicationConfiguration>
 */
class ApplicationConfigurationFactory extends Factory
{
    protected $model = ApplicationConfiguration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = ApplicationConfigurationType::FeatureFlags;

        return [
            'uuid' => (string) Str::uuid(),
            'environment_id' => null,
            'type' => $type->value,
            'name' => $type->label(),
            'description' => fake()->optional()->sentence(),
            'payload' => $type->defaultPayload(),
            'status' => ApplicationConfigurationStatus::Draft->value,
            'version' => 1,
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function forApplication(Application $application): static
    {
        return $this->state(fn (): array => [
            'application_id' => $application->id,
        ]);
    }

    public function ofType(ApplicationConfigurationType $type): static
    {
        return $this->state(fn (): array => [
            'type' => $type->value,
            'name' => $type->label(),
            'payload' => $type->defaultPayload(),
        ]);
    }
}

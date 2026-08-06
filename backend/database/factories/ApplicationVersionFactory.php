<?php

namespace Database\Factories;

use App\Domains\Applications\Enums\ApplicationVersionStatus;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApplicationVersion>
 */
class ApplicationVersionFactory extends Factory
{
    protected $model = ApplicationVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $major = fake()->numberBetween(0, 5);
        $minor = fake()->numberBetween(0, 20);
        $patch = fake()->numberBetween(0, 30);

        return [
            'uuid' => (string) Str::uuid(),
            'version_number' => "{$major}.{$minor}.{$patch}",
            'major' => $major,
            'minor' => $minor,
            'patch' => $patch,
            'build_number' => (string) fake()->numberBetween(100, 9999),
            'status' => ApplicationVersionStatus::Draft->value,
            'release_date' => null,
            'minimum_supported_version' => '1.0.0',
            'release_notes' => fake()->optional()->paragraph(),
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

    public function production(): static
    {
        return $this->state(fn (): array => [
            'status' => ApplicationVersionStatus::Production->value,
            'release_date' => now(),
        ]);
    }
}

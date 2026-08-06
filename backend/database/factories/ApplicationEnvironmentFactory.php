<?php

namespace Database\Factories;

use App\Domains\Applications\Enums\ApplicationEnvironmentHealthStatus;
use App\Domains\Applications\Enums\ApplicationEnvironmentStatus;
use App\Domains\Applications\Enums\ApplicationEnvironmentType;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationEnvironment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApplicationEnvironment>
 */
class ApplicationEnvironmentFactory extends Factory
{
    protected $model = ApplicationEnvironment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(ApplicationEnvironmentType::values());

        return [
            'uuid' => (string) Str::uuid(),
            'name' => Str::title($type).' Environment',
            'slug' => $type,
            'type' => $type,
            'api_url' => 'https://api.'.$type.'.example.com',
            'web_url' => 'https://'.$type.'.example.com',
            'status' => ApplicationEnvironmentStatus::Active->value,
            'health_status' => ApplicationEnvironmentHealthStatus::Unknown->value,
            'last_health_check' => null,
            'variables' => [
                'APP_ENV' => $type,
                'API_KEY' => 'secret-'.Str::random(12),
            ],
            'is_current' => false,
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

    public function current(): static
    {
        return $this->state(fn (): array => [
            'is_current' => true,
        ]);
    }
}

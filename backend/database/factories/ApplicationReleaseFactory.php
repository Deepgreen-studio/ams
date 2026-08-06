<?php

namespace Database\Factories;

use App\Domains\Applications\Enums\ApplicationReleaseApprovalStatus;
use App\Domains\Applications\Enums\ApplicationReleaseRollbackStatus;
use App\Domains\Applications\Enums\ApplicationReleaseStatus;
use App\Domains\Applications\Enums\ApplicationReleaseType;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationRelease;
use App\Domains\Applications\Models\ApplicationVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApplicationRelease>
 */
class ApplicationReleaseFactory extends Factory
{
    protected $model = ApplicationRelease::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => 'Release '.fake()->bothify('?#.#'),
            'version_label' => '1.0.0',
            'release_type' => ApplicationReleaseType::Minor->value,
            'status' => ApplicationReleaseStatus::Planned->value,
            'approval_status' => ApplicationReleaseApprovalStatus::NotRequired->value,
            'rollback_status' => ApplicationReleaseRollbackStatus::None->value,
            'scheduled_at' => null,
            'deployment_date' => null,
            'plan_summary' => fake()->optional()->sentence(),
            'metadata' => null,
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

    public function forVersion(ApplicationVersion $version): static
    {
        return $this->state(fn (): array => [
            'application_id' => $version->application_id,
            'application_version_id' => $version->id,
            'version_label' => $version->version_number,
        ]);
    }
}

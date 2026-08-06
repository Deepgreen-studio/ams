<?php

namespace Database\Factories;

use App\Domains\Compliance\Enums\PolicyApprovalStatus;
use App\Domains\Compliance\Models\PolicyApproval;
use App\Domains\Compliance\Models\PolicyDocument;
use App\Domains\Compliance\Models\PolicyVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PolicyApproval>
 */
class PolicyApprovalFactory extends Factory
{
    protected $model = PolicyApproval::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'status' => PolicyApprovalStatus::Pending->value,
            'requested_by' => null,
            'reviewed_by' => null,
            'comments' => null,
            'requested_at' => now(),
            'decided_at' => null,
        ];
    }

    public function forPolicy(PolicyDocument $policy, PolicyVersion $version): static
    {
        return $this->state(fn (): array => [
            'policy_id' => $policy->id,
            'policy_version_id' => $version->id,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => PolicyApprovalStatus::Pending->value,
            'requested_at' => now(),
        ]);
    }
}

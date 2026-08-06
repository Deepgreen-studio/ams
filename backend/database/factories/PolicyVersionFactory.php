<?php

namespace Database\Factories;

use App\Domains\Compliance\Enums\PolicyDocumentStatus;
use App\Domains\Compliance\Models\PolicyDocument;
use App\Domains\Compliance\Models\PolicyVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PolicyVersion>
 */
class PolicyVersionFactory extends Factory
{
    protected $model = PolicyVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);
        $body = fake()->paragraphs(2, true);

        return [
            'uuid' => (string) Str::uuid(),
            'version' => 1,
            'status' => PolicyDocumentStatus::Draft->value,
            'title' => $title,
            'body' => $body,
            'snapshot' => [
                'title' => $title,
                'body' => $body,
                'description' => null,
                'policy_type' => 'privacy_policy',
                'status' => PolicyDocumentStatus::Draft->value,
            ],
            'reason' => 'Initial version',
            'is_restore' => false,
            'restored_from_version' => null,
            'created_by' => null,
            'created_at' => now(),
        ];
    }

    public function forPolicy(PolicyDocument $policy): static
    {
        return $this->state(fn (): array => [
            'policy_id' => $policy->id,
            'title' => $policy->title,
            'body' => $policy->body,
            'status' => $policy->status?->value ?? PolicyDocumentStatus::Draft->value,
            'version' => $policy->current_version,
            'snapshot' => [
                'title' => $policy->title,
                'slug' => $policy->slug,
                'description' => $policy->description,
                'body' => $policy->body,
                'policy_type' => $policy->policy_type?->value ?? $policy->policy_type,
                'status' => $policy->status?->value ?? $policy->status,
                'effective_at' => optional($policy->effective_at)?->toIso8601String(),
                'expires_at' => optional($policy->expires_at)?->toIso8601String(),
                'review_due_at' => optional($policy->review_due_at)?->toDateString(),
                'content_id' => $policy->content_id,
            ],
        ]);
    }
}

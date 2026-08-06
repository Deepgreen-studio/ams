<?php

namespace Database\Factories;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\PolicyDocumentStatus;
use App\Domains\Compliance\Enums\PolicyType;
use App\Domains\Compliance\Models\PolicyDocument;
use App\Domains\Content\Models\Content;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PolicyDocument>
 */
class PolicyDocumentFactory extends Factory
{
    protected $model = PolicyDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'uuid' => (string) Str::uuid(),
            'policy_number' => sprintf('POL-%s-%05d', now()->format('Ymd'), fake()->unique()->numberBetween(1, 99999)),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'policy_type' => PolicyType::PrivacyPolicy->value,
            'description' => fake()->paragraph(),
            'body' => fake()->paragraphs(3, true),
            'status' => PolicyDocumentStatus::Draft->value,
            'current_version' => 1,
            'content_id' => null,
            'review_due_at' => now()->addYear()->toDateString(),
            'assigned_to' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => [
            'company_id' => $company->id,
        ]);
    }

    public function linkedToContent(Content $content): static
    {
        return $this->state(fn (): array => [
            'content_id' => $content->id,
        ]);
    }

    public function inReview(): static
    {
        return $this->state(fn (): array => [
            'status' => PolicyDocumentStatus::Review->value,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => PolicyDocumentStatus::Published->value,
            'published_at' => now(),
            'current_version' => 2,
        ]);
    }
}

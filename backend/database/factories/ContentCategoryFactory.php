<?php

namespace Database\Factories;

use App\Domains\Content\Models\ContentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentCategory>
 */
class ContentCategoryFactory extends Factory
{
    protected $model = ContentCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'uuid' => (string) Str::uuid(),
            'parent_id' => null,
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
            'seo_title' => null,
            'seo_description' => null,
            'is_active' => true,
            'sort_order' => 0,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function childOf(ContentCategory $parent): static
    {
        return $this->state(fn (): array => ['parent_id' => $parent->id]);
    }
}

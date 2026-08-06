<?php

namespace Database\Factories;

use App\Domains\Content\Models\ContentTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentTag>
 */
class ContentTagFactory extends Factory
{
    protected $model = ContentTag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'uuid' => (string) Str::uuid(),
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
}

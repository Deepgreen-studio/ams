<?php

namespace Database\Factories;

use App\Domains\Content\Models\ContentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentType>
 */
class ContentTypeFactory extends Factory
{
    protected $model = ContentType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'uuid' => (string) Str::uuid(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 100,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}

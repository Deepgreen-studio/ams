<?php

namespace Database\Factories;

use App\Domains\Content\Enums\ContentStatusSlug;
use App\Domains\Content\Enums\ContentTypeSlug;
use App\Domains\Content\Models\Content;
use App\Domains\Content\Models\ContentStatus;
use App\Domains\Content\Models\ContentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Content>
 */
class ContentFactory extends Factory
{
    protected $model = Content::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'uuid' => (string) Str::uuid(),
            'content_type_id' => ContentType::query()->where('slug', ContentTypeSlug::Page->value)->value('id')
                ?? ContentType::factory(),
            'content_status_id' => ContentStatus::query()->where('slug', ContentStatusSlug::Draft->value)->value('id'),
            'content_category_id' => null,
            'title' => rtrim($title, '.'),
            'slug' => Str::slug($title),
            'summary' => fake()->optional()->sentence(10),
            'excerpt' => fake()->optional()->sentence(12),
            'body' => fake()->optional()->paragraphs(3, true),
            'body_format' => 'rich',
            'editor_json' => null,
            'featured_image' => null,
            'seo_title' => null,
            'seo_description' => null,
            'seo_keywords' => null,
            'canonical_url' => null,
            'og_title' => null,
            'og_description' => null,
            'og_image' => null,
            'twitter_card' => null,
            'twitter_title' => null,
            'twitter_description' => null,
            'twitter_image' => null,
            'schema_type' => null,
            'schema_json' => null,
            'metadata' => null,
            'is_featured' => false,
            'view_count' => 0,
            'last_viewed_at' => null,
            'sort_order' => 0,
            'version' => 1,
            'published_at' => null,
            'published_by' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(function (): array {
            $statusId = ContentStatus::query()
                ->where('slug', ContentStatusSlug::Published->value)
                ->value('id');

            return [
                'content_status_id' => $statusId,
                'published_at' => now(),
            ];
        });
    }

    public function forType(ContentType|string $type): static
    {
        return $this->state(function () use ($type): array {
            if ($type instanceof ContentType) {
                return ['content_type_id' => $type->id];
            }

            $id = ContentType::query()->where('slug', $type)->orWhere('uuid', $type)->value('id');

            return ['content_type_id' => $id];
        });
    }
}

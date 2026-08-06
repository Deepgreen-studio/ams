<?php

namespace App\Domains\Content\Resources;

use App\Domains\Content\Services\CmsSeoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HeadlessContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CmsSeoService $seoService */
        $seoService = app(CmsSeoService::class);
        $includeBody = filter_var($request->query('include_body', true), FILTER_VALIDATE_BOOLEAN);
        $includeSeo = filter_var($request->query('include_seo', true), FILTER_VALIDATE_BOOLEAN);

        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'excerpt' => $this->excerpt,
            'body' => $this->when($includeBody, $this->body),
            'body_format' => $this->body_format?->value ?? $this->body_format,
            'featured_image' => $this->featured_image,
            'is_featured' => (bool) $this->is_featured,
            'view_count' => (int) ($this->view_count ?? 0),
            'published_at' => $this->published_at,
            'updated_at' => $this->updated_at,
            'type' => $this->whenLoaded('type', fn () => $this->type ? [
                'uuid' => $this->type->uuid,
                'name' => $this->type->name,
                'slug' => $this->type->slug,
            ] : null),
            'status' => $this->whenLoaded('status', fn () => $this->status ? [
                'uuid' => $this->status->uuid,
                'name' => $this->status->name,
                'slug' => $this->status->slug,
            ] : null),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($category) => [
                'uuid' => $category->uuid,
                'name' => $category->name,
                'slug' => $category->slug,
            ])->values()),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'uuid' => $tag->uuid,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->values()),
            'seo' => $this->when($includeSeo, fn () => $seoService->buildForContent($this->resource)),
            'url' => $seoService->publicContentUrl($this->resource),
        ];
    }
}

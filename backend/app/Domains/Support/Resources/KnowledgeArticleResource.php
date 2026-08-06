<?php

namespace App\Domains\Support\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnowledgeArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $userFeedback = null;
        if ($user && $this->relationLoaded('feedback')) {
            $match = $this->feedback->firstWhere('user_id', $user->id);
            $userFeedback = $match ? (bool) $match->is_helpful : null;
        }

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'type' => $this->type?->value ?? $this->type,
            'type_label' => $this->type?->label(),
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'body' => $this->body,
            'body_format' => $this->body_format,
            'video_url' => $this->video_url,
            'featured_image' => $this->featured_image,
            'sync_from_cms' => (bool) $this->sync_from_cms,
            'is_featured' => (bool) $this->is_featured,
            'view_count' => (int) $this->view_count,
            'helpful_count' => (int) $this->helpful_count,
            'not_helpful_count' => (int) $this->not_helpful_count,
            'user_feedback' => $userFeedback,
            'version' => (int) $this->version,
            'sort_order' => (int) $this->sort_order,
            'published_at' => $this->published_at,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'uuid' => $this->category->uuid,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'uuid' => $tag->uuid,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->values()),
            'content' => $this->whenLoaded('content', fn () => $this->content ? [
                'uuid' => $this->content->uuid,
                'title' => $this->content->title,
                'slug' => $this->content->slug,
                'version' => $this->content->version,
                'published_at' => $this->content->published_at,
                'type' => $this->content->relationLoaded('type') ? ($this->content->type?->slug ?? null) : null,
            ] : null),
            'related_articles' => KnowledgeArticleSummaryResource::collection($this->whenLoaded('relatedArticles')),
            'author' => $this->whenLoaded('author', fn () => $this->author ? [
                'uuid' => $this->author->uuid,
                'full_name' => $this->author->full_name,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

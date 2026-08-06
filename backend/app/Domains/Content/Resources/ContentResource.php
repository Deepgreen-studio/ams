<?php

namespace App\Domains\Content\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'body_format' => $this->body_format?->value ?? $this->body_format,
            'editor_json' => $this->editor_json,
            'featured_image' => $this->featured_image,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'seo_keywords' => $this->seo_keywords,
            'canonical_url' => $this->canonical_url,
            'og_title' => $this->og_title,
            'og_description' => $this->og_description,
            'og_image' => $this->og_image,
            'twitter_card' => $this->twitter_card,
            'twitter_title' => $this->twitter_title,
            'twitter_description' => $this->twitter_description,
            'twitter_image' => $this->twitter_image,
            'schema_type' => $this->schema_type,
            'schema_json' => $this->schema_json,
            'metadata' => $this->metadata,
            'is_featured' => (bool) $this->is_featured,
            'view_count' => (int) ($this->view_count ?? 0),
            'last_viewed_at' => $this->last_viewed_at,
            'sort_order' => (int) $this->sort_order,
            'version' => (int) ($this->version ?? 1),
            'current_workflow_level' => $this->current_workflow_level,
            'last_workflow_comment' => $this->last_workflow_comment,
            'published_at' => $this->published_at,
            'submitted_at' => $this->submitted_at,
            'reviewed_at' => $this->reviewed_at,
            'approved_at' => $this->approved_at,
            'rejected_at' => $this->rejected_at,
            'last_autosaved_at' => $this->last_autosaved_at,
            'content_type_id' => $this->content_type_id,
            'content_status_id' => $this->content_status_id,
            'content_category_id' => $this->content_category_id,
            'type' => $this->whenLoaded('type', function () {
                return $this->type ? [
                    'id' => $this->type->id,
                    'uuid' => $this->type->uuid,
                    'name' => $this->type->name,
                    'slug' => $this->type->slug,
                    'description' => $this->type->description ?? null,
                ] : null;
            }),
            'status' => $this->whenLoaded('status', function () {
                return $this->status ? [
                    'id' => $this->status->id,
                    'uuid' => $this->status->uuid,
                    'name' => $this->status->name,
                    'slug' => $this->status->slug,
                    'color' => $this->status->color,
                ] : null;
            }),
            'category' => $this->whenLoaded('category', function () {
                return $this->category ? [
                    'id' => $this->category->id,
                    'uuid' => $this->category->uuid,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ] : null;
            }),
            'categories' => $this->whenLoaded('categories', function () {
                return $this->categories->map(fn ($category) => [
                    'id' => $category->id,
                    'uuid' => $category->uuid,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])->values();
            }),
            'tags' => $this->whenLoaded('tags', function () {
                return $this->tags->map(fn ($tag) => [
                    'id' => $tag->id,
                    'uuid' => $tag->uuid,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ])->values();
            }),
            'publisher' => $this->whenLoaded('publisher', function () {
                return $this->publisher ? [
                    'id' => $this->publisher->id,
                    'uuid' => $this->publisher->uuid,
                    'full_name' => $this->publisher->full_name,
                    'email' => $this->publisher->email,
                ] : null;
            }),
            'submitter' => $this->whenLoaded('submitter', fn () => $this->userSummary($this->submitter)),
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->userSummary($this->reviewer)),
            'approver' => $this->whenLoaded('approver', fn () => $this->userSummary($this->approver)),
            'rejector' => $this->whenLoaded('rejector', fn () => $this->userSummary($this->rejector)),
            'creator' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id' => $this->creator->id,
                    'uuid' => $this->creator->uuid,
                    'full_name' => $this->creator->full_name,
                    'email' => $this->creator->email,
                ] : null;
            }),
            'updater' => $this->whenLoaded('updater', function () {
                return $this->updater ? [
                    'id' => $this->updater->id,
                    'uuid' => $this->updater->uuid,
                    'full_name' => $this->updater->full_name,
                    'email' => $this->updater->email,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }

    /**
     * @return array{id: int, uuid: string, full_name: string|null, email: string|null}|null
     */
    protected function userSummary(mixed $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'full_name' => $user->full_name,
            'email' => $user->email,
        ];
    }
}

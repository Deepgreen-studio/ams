<?php

namespace App\Domains\Support\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnowledgeArticleSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type?->value ?? $this->type,
            'type_label' => $this->type?->label(),
            'summary' => $this->summary,
            'status' => $this->status?->value ?? $this->status,
            'is_featured' => (bool) $this->is_featured,
            'view_count' => (int) $this->view_count,
            'helpful_count' => (int) $this->helpful_count,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'uuid' => $this->category->uuid,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'published_at' => $this->published_at,
        ];
    }
}

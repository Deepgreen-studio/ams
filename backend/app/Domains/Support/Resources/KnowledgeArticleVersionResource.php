<?php

namespace App\Domains\Support\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnowledgeArticleVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'version' => (int) $this->version,
            'title' => $this->title,
            'body' => $this->body,
            'body_format' => $this->body_format,
            'summary' => $this->summary,
            'status' => $this->status,
            'snapshot' => $this->snapshot,
            'reason' => $this->reason,
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'uuid' => $this->creator->uuid,
                'full_name' => $this->creator->full_name,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}

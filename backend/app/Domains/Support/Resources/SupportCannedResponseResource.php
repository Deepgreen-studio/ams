<?php

namespace App\Domains\Support\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportCannedResponseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'shortcut' => $this->shortcut,
            'body' => $this->body,
            'body_format' => $this->body_format,
            'visibility' => $this->visibility?->value ?? $this->visibility,
            'visibility_label' => $this->visibility?->label(),
            'is_active' => (bool) $this->is_active,
            'usage_count' => (int) $this->usage_count,
            'sort_order' => (int) $this->sort_order,
            'owner' => $this->whenLoaded('owner', fn () => $this->owner ? [
                'uuid' => $this->owner->uuid,
                'full_name' => $this->owner->full_name,
                'email' => $this->owner->email,
            ] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'uuid' => $this->creator->uuid,
                'full_name' => $this->creator->full_name,
                'email' => $this->creator->email,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

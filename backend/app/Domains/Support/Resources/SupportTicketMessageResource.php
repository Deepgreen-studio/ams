<?php

namespace App\Domains\Support\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewerId = $request->user()?->id;
        $isRead = false;

        if ($viewerId) {
            if ((int) $this->author_id === (int) $viewerId) {
                $isRead = true;
            } elseif ($this->relationLoaded('reads')) {
                $isRead = $this->reads->contains(fn ($read) => (int) $read->user_id === (int) $viewerId);
            }
        }

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'visibility' => $this->visibility?->value ?? $this->visibility,
            'visibility_label' => $this->visibility?->label(),
            'author_type' => $this->author_type?->value ?? $this->author_type,
            'author_type_label' => $this->author_type?->label(),
            'body' => $this->body,
            'body_format' => $this->body_format,
            'is_system' => (bool) $this->is_system,
            'is_read' => $isRead,
            'author' => $this->whenLoaded('author', fn () => $this->author ? [
                'uuid' => $this->author->uuid,
                'full_name' => $this->author->full_name,
                'email' => $this->author->email,
            ] : null),
            'attachments' => SupportTicketAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

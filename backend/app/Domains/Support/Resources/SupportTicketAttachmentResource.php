<?php

namespace App\Domains\Support\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'attachment_type' => $this->attachment_type?->value ?? $this->attachment_type,
            'attachment_type_label' => $this->attachment_type?->label(),
            'original_filename' => $this->original_filename,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'is_image' => $this->isImage(),
            'is_video' => $this->isVideo(),
            'is_previewable' => $this->isPreviewable(),
            'ticket_message_id' => $this->ticket_message_id,
            'uploader' => $this->whenLoaded('uploader', fn () => $this->uploader ? [
                'uuid' => $this->uploader->uuid,
                'full_name' => $this->uploader->full_name,
                'email' => $this->uploader->email,
            ] : null),
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

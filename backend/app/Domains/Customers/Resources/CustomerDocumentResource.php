<?php

namespace App\Domains\Customers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'customer_id' => $this->customer_id,
            'document_group_uuid' => $this->document_group_uuid,
            'version' => $this->version,
            'is_current' => $this->is_current,
            'name' => $this->name,
            'category' => $this->category?->value ?? $this->category,
            'category_label' => $this->category instanceof \BackedEnum
                ? $this->category->label()
                : null,
            'status' => $this->status?->value ?? $this->status,
            'original_filename' => $this->original_filename,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'url' => $this->url(),
            'is_previewable' => $this->isPreviewable(),
            'expires_at' => $this->expires_at,
            'notes' => $this->notes,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'uuid' => $this->customer->uuid,
                'display_name' => $this->customer->display_name,
                'email' => $this->customer->email,
            ] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'uuid' => $this->creator->uuid,
                'full_name' => $this->creator->full_name,
                'email' => $this->creator->email,
            ] : null),
            'updater' => $this->whenLoaded('updater', fn () => $this->updater ? [
                'uuid' => $this->updater->uuid,
                'full_name' => $this->updater->full_name,
                'email' => $this->updater->email,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

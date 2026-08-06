<?php

namespace App\Domains\Customers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'customer_id' => $this->customer_id,
            'note_type' => $this->note_type?->value ?? $this->note_type,
            'note_type_label' => $this->note_type instanceof \BackedEnum ? $this->note_type->label() : null,
            'title' => $this->title,
            'body' => $this->body,
            'is_pinned' => $this->is_pinned,
            'status' => $this->status?->value ?? $this->status,
            'occurred_at' => $this->occurred_at,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'uuid' => $this->customer->uuid,
                'display_name' => $this->customer->display_name,
            ] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'uuid' => $this->creator->uuid,
                'full_name' => $this->creator->full_name,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

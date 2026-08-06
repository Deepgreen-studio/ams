<?php

namespace App\Domains\Customers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerCommunicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'customer_id' => $this->customer_id,
            'type' => $this->type?->value ?? $this->type,
            'type_label' => $this->type instanceof \BackedEnum ? $this->type->label() : null,
            'direction' => $this->direction?->value ?? $this->direction,
            'subject' => $this->subject,
            'body' => $this->body,
            'status' => $this->status?->value ?? $this->status,
            'channel_reference' => $this->channel_reference,
            'participants' => $this->participants,
            'duration_seconds' => $this->duration_seconds,
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

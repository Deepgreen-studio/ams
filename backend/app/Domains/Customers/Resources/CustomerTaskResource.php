<?php

namespace App\Domains\Customers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'customer_id' => $this->customer_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status?->value ?? $this->status,
            'priority' => $this->priority?->value ?? $this->priority,
            'due_at' => $this->due_at,
            'remind_at' => $this->remind_at,
            'completed_at' => $this->completed_at,
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'uuid' => $this->assignee->uuid,
                'full_name' => $this->assignee->full_name,
                'email' => $this->assignee->email,
            ] : null),
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

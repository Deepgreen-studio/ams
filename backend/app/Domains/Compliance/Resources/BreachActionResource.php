<?php

namespace App\Domains\Compliance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BreachActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'data_breach_id' => $this->data_breach_id,
            'action_type' => $this->action_type?->value ?? $this->action_type,
            'action_type_label' => $this->action_type?->label(),
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'performed_by' => $this->performed_by,
            'performer' => $this->whenLoaded('performer', function () {
                return $this->performer ? [
                    'id' => $this->performer->id,
                    'uuid' => $this->performer->uuid,
                    'full_name' => $this->performer->full_name,
                    'email' => $this->performer->email,
                ] : null;
            }),
            'due_at' => $this->due_at,
            'completed_at' => $this->completed_at,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

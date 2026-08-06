<?php

namespace App\Domains\Workflows\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company?->uuid,
            'company' => $this->whenLoaded('company', fn () => [
                'uuid' => $this->company?->uuid,
                'company_name' => $this->company?->company_name,
            ]),
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type?->value ?? $this->type,
            'type_label' => $this->type?->label(),
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'version' => (int) $this->version,
            'is_enabled' => (bool) $this->is_enabled,
            'metadata' => $this->metadata,
            'steps' => WorkflowStepResource::collection($this->whenLoaded('steps')),
            'creator' => $this->whenLoaded('creator', fn () => [
                'uuid' => $this->creator?->uuid,
                'full_name' => $this->creator?->full_name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

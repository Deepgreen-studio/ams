<?php

namespace App\Domains\Workflows\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'action' => $this->action?->value ?? $this->action,
            'action_label' => $this->action?->label(),
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'comment' => $this->comment,
            'payload' => $this->payload,
            'step' => $this->whenLoaded('step', fn () => [
                'uuid' => $this->step?->uuid,
                'name' => $this->step?->name,
                'step_key' => $this->step?->step_key,
                'step_type' => $this->step?->step_type?->value ?? $this->step?->step_type,
            ]),
            'actor' => $this->whenLoaded('actor', fn () => [
                'uuid' => $this->actor?->uuid,
                'full_name' => $this->actor?->full_name,
                'email' => $this->actor?->email,
            ]),
            'instance' => $this->whenLoaded('instance', fn () => [
                'uuid' => $this->instance?->uuid,
                'subject_label' => $this->instance?->subject_label,
                'status' => $this->instance?->status?->value ?? $this->instance?->status,
                'workflow' => $this->instance?->relationLoaded('workflow') ? [
                    'uuid' => $this->instance?->workflow?->uuid,
                    'name' => $this->instance?->workflow?->name,
                ] : null,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}

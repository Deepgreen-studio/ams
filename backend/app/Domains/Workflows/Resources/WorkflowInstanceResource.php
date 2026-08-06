<?php

namespace App\Domains\Workflows\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowInstanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'workflow_id' => $this->workflow?->uuid,
            'workflow' => $this->whenLoaded('workflow', fn () => [
                'uuid' => $this->workflow?->uuid,
                'name' => $this->workflow?->name,
                'type' => $this->workflow?->type?->value ?? $this->workflow?->type,
                'type_label' => $this->workflow?->type?->label(),
            ]),
            'company_id' => $this->company?->uuid,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'subject_label' => $this->subject_label,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'current_step' => $this->whenLoaded('currentStep', fn () => $this->currentStep
                ? new WorkflowStepResource($this->currentStep)
                : null),
            'active_step_keys' => $this->active_step_keys ?? [],
            'pending_approvers' => $this->pending_approvers ?? [],
            'context' => $this->context,
            'metadata' => $this->metadata,
            'started_at' => $this->started_at,
            'due_at' => $this->due_at,
            'completed_at' => $this->completed_at,
            'starter' => $this->whenLoaded('starter', fn () => [
                'uuid' => $this->starter?->uuid,
                'full_name' => $this->starter?->full_name,
            ]),
            'logs' => WorkflowLogResource::collection($this->whenLoaded('logs')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

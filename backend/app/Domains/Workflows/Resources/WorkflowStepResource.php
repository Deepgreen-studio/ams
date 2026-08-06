<?php

namespace App\Domains\Workflows\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowStepResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'step_key' => $this->step_key,
            'step_type' => $this->step_type?->value ?? $this->step_type,
            'step_type_label' => $this->step_type?->label(),
            'sort_order' => (int) $this->sort_order,
            'position_x' => (int) $this->position_x,
            'position_y' => (int) $this->position_y,
            'config' => $this->config ?? [],
            'next_step_keys' => $this->next_step_keys ?? [],
            'on_approve_step_key' => $this->on_approve_step_key,
            'on_reject_step_key' => $this->on_reject_step_key,
            'is_required' => (bool) $this->is_required,
        ];
    }
}

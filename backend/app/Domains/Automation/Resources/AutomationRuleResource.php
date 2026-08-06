<?php

namespace App\Domains\Automation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutomationRuleResource extends JsonResource
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
            'trigger_type' => $this->trigger_type?->value ?? $this->trigger_type,
            'trigger_type_label' => $this->trigger_type?->label(),
            'event_key' => $this->event_key,
            'schedule_cron' => $this->schedule_cron,
            'schedule_timezone' => $this->schedule_timezone,
            'delay_minutes' => $this->delay_minutes,
            'condition_logic' => $this->condition_logic,
            'is_enabled' => (bool) $this->is_enabled,
            'priority' => (int) $this->priority,
            'last_triggered_at' => $this->last_triggered_at,
            'next_run_at' => $this->next_run_at,
            'metadata' => $this->metadata,
            'conditions' => AutomationConditionResource::collection($this->whenLoaded('conditions')),
            'actions' => AutomationActionResource::collection($this->whenLoaded('actions')),
            'creator' => $this->whenLoaded('creator', fn () => [
                'uuid' => $this->creator?->uuid,
                'full_name' => $this->creator?->full_name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

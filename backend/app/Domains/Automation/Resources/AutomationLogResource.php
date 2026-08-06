<?php

namespace App\Domains\Automation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutomationLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'trigger_type' => $this->trigger_type?->value ?? $this->trigger_type,
            'event_key' => $this->event_key,
            'context' => $this->context,
            'actions_result' => $this->actions_result,
            'message' => $this->message,
            'error_message' => $this->error_message,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'rule' => $this->whenLoaded('rule', fn () => [
                'uuid' => $this->rule?->uuid,
                'name' => $this->rule?->name,
                'trigger_type' => $this->rule?->trigger_type?->value ?? $this->rule?->trigger_type,
                'event_key' => $this->rule?->event_key,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}

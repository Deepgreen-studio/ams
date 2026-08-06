<?php

namespace App\Domains\Support\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportSlaEscalationRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'level' => $this->level?->value ?? $this->level,
            'level_label' => $this->level?->label(),
            'trigger' => $this->trigger?->value ?? $this->trigger,
            'trigger_label' => $this->trigger?->label(),
            'sort_order' => $this->sort_order,
            'notify_role' => $this->notify_role,
            'reassign_to_manager' => (bool) $this->reassign_to_manager,
            'is_active' => (bool) $this->is_active,
            'metadata' => $this->metadata,
        ];
    }
}

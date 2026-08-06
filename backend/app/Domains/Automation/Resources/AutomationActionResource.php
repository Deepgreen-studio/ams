<?php

namespace App\Domains\Automation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutomationActionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'action_type' => $this->action_type?->value ?? $this->action_type,
            'action_type_label' => $this->action_type?->label(),
            'config' => $this->config ?? [],
            'is_enabled' => (bool) $this->is_enabled,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}

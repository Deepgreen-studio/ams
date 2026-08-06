<?php

namespace App\Domains\Audit\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $properties = $this->properties?->toArray() ?? [];

        return [
            'id' => $this->id,
            'uuid' => (string) $this->id,
            'module' => $this->log_name,
            'action' => $this->event,
            'description' => $this->description,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'old_values' => $properties['old'] ?? $properties['attributes'] ?? null,
            'new_values' => $properties['attributes'] ?? $properties['new'] ?? $properties,
            'properties' => $properties,
            'ip_address' => $properties['ip'] ?? null,
            'user_agent' => $properties['user_agent'] ?? null,
            'user' => $this->whenLoaded('causer', fn () => $this->causer ? [
                'id' => $this->causer->id,
                'uuid' => $this->causer->uuid ?? null,
                'full_name' => $this->causer->full_name ?? null,
                'email' => $this->causer->email ?? null,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}

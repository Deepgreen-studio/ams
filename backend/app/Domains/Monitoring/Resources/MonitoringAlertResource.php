<?php

namespace App\Domains\Monitoring\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringAlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company' => $this->whenLoaded('company', fn () => $this->company ? [
                'uuid' => $this->company->uuid,
                'company_name' => $this->company->company_name,
            ] : null),
            'name' => $this->name,
            'metric' => $this->metric?->value ?? $this->metric,
            'operator' => $this->operator,
            'threshold' => $this->threshold,
            'is_enabled' => $this->is_enabled,
            'cooldown_minutes' => $this->cooldown_minutes,
            'channels' => $this->channels ?? [],
            'description' => $this->description,
            'last_triggered_at' => $this->last_triggered_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

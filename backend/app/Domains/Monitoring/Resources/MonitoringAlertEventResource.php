<?php

namespace App\Domains\Monitoring\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringAlertEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'severity' => $this->severity,
            'status' => $this->status,
            'metric_value' => $this->metric_value,
            'message' => $this->message,
            'context' => $this->context ?? [],
            'alert' => $this->whenLoaded('alert', fn () => $this->alert ? [
                'uuid' => $this->alert->uuid,
                'name' => $this->alert->name,
                'metric' => $this->alert->metric?->value ?? $this->alert->metric,
            ] : null),
            'acknowledged_at' => $this->acknowledged_at,
            'created_at' => $this->created_at,
        ];
    }
}

<?php

namespace App\Domains\Applications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationMonitoringAlertEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'application_id' => $this->application_id,
            'alert_id' => $this->alert_id,
            'metric' => $this->metric,
            'threshold' => $this->threshold,
            'observed_value' => $this->observed_value,
            'severity' => $this->severity?->value ?? $this->severity,
            'status' => $this->status,
            'message' => $this->message,
            'context' => $this->context,
            'triggered_at' => $this->triggered_at,
            'acknowledged_at' => $this->acknowledged_at,
            'alert' => $this->whenLoaded('alert', function () {
                return $this->alert ? [
                    'id' => $this->alert->id,
                    'uuid' => $this->alert->uuid,
                    'name' => $this->alert->name,
                ] : null;
            }),
            'acknowledger' => $this->whenLoaded('acknowledger', function () {
                return $this->acknowledger ? [
                    'id' => $this->acknowledger->id,
                    'uuid' => $this->acknowledger->uuid,
                    'full_name' => $this->acknowledger->full_name,
                    'email' => $this->acknowledger->email,
                ] : null;
            }),
            'created_at' => $this->created_at,
        ];
    }
}

<?php

namespace App\Domains\Monitoring\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company_id,
            'service_key' => $this->service_key,
            'service_type' => $this->service_type?->value ?? $this->service_type,
            'name' => $this->name,
            'status' => $this->status?->value ?? $this->status,
            'last_check_at' => $this->last_check_at,
            'last_success_at' => $this->last_success_at,
            'last_failure_at' => $this->last_failure_at,
            'consecutive_failures' => $this->consecutive_failures,
            'uptime_percent' => $this->uptime_percent,
            'avg_response_ms' => $this->avg_response_ms,
            'error_rate' => $this->error_rate,
            'metadata' => $this->metadata,
            'updated_at' => $this->updated_at,
        ];
    }
}

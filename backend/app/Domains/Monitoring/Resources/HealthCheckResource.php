<?php

namespace App\Domains\Monitoring\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthCheckResource extends JsonResource
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
            'monitoring_snapshot_id' => $this->monitoring_snapshot_id,
            'check_type' => $this->check_type?->value ?? $this->check_type,
            'check_type_label' => $this->check_type instanceof \App\Domains\Monitoring\Enums\HealthCheckType
                ? $this->check_type->label()
                : $this->check_type,
            'name' => $this->name,
            'status' => $this->status?->value ?? $this->status,
            'response_ms' => $this->response_ms,
            'message' => $this->message,
            'metadata' => $this->metadata,
            'checked_at' => $this->checked_at,
            'created_at' => $this->created_at,
        ];
    }
}

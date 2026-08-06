<?php

namespace App\Domains\Applications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationHealthMetricResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'application_id' => $this->application_id,
            'application_version_id' => $this->application_version_id,
            'version_label' => $this->version_label,
            'recorded_at' => $this->recorded_at,
            'health_score' => $this->health_score,
            'crash_rate' => $this->crash_rate,
            'anr_rate' => $this->anr_rate,
            'api_error_rate' => $this->api_error_rate,
            'avg_response_time_ms' => $this->avg_response_time_ms,
            'avg_memory_usage_mb' => $this->avg_memory_usage_mb,
            'avg_battery_usage' => $this->avg_battery_usage,
            'crash_count' => $this->crash_count,
            'anr_count' => $this->anr_count,
            'api_error_count' => $this->api_error_count,
            'sample_size' => $this->sample_size,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
        ];
    }
}

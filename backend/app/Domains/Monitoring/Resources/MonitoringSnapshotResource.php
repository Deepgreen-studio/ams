<?php

namespace App\Domains\Monitoring\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'scope' => $this->scope,
            'health_score' => $this->health_score,
            'performance_score' => $this->performance_score,
            'uptime_percent' => $this->uptime_percent,
            'downtime_percent' => $this->downtime_percent,
            'error_rate' => $this->error_rate,
            'avg_response_ms' => $this->avg_response_ms,
            'webhook_success_rate' => $this->webhook_success_rate,
            'queue_health_score' => $this->queue_health_score,
            'availability_status' => $this->availability_status,
            'authentication_status' => $this->authentication_status,
            'rate_limit_status' => $this->rate_limit_status,
            'server_status' => $this->server_status,
            'created_at' => $this->created_at,
        ];
    }
}

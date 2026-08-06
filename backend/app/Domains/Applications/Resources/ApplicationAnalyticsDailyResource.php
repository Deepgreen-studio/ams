<?php

namespace App\Domains\Applications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationAnalyticsDailyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'application_id' => $this->application_id,
            'metric_date' => optional($this->metric_date)->toDateString(),
            'active_users' => $this->active_users,
            'daily_users' => $this->daily_users,
            'monthly_users' => $this->monthly_users,
            'avg_session_seconds' => $this->avg_session_seconds,
            'retention_d1' => $this->retention_d1,
            'retention_d7' => $this->retention_d7,
            'retention_d30' => $this->retention_d30,
            'installs' => $this->installs,
            'uninstalls' => $this->uninstalls,
            'sessions' => $this->sessions,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

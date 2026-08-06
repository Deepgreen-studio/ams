<?php

namespace App\Domains\Customers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAnalyticsSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'customer_id' => $this->customer_id,
            'snapshot_date' => optional($this->snapshot_date)->toDateString(),
            'applications_total' => $this->applications_total,
            'applications_active' => $this->applications_active,
            'integrations_total' => $this->integrations_total,
            'api_usage_count' => $this->api_usage_count,
            'login_activity_count' => $this->login_activity_count,
            'support_tickets_open' => $this->support_tickets_open,
            'support_tickets_total' => $this->support_tickets_total,
            'subscription_status' => $this->subscription_status,
            'subscription_active' => $this->subscription_active,
            'health_score' => $this->health_score,
            'activity_score' => $this->activity_score,
            'risk_level' => $this->risk_level?->value ?? $this->risk_level,
            'metrics' => $this->metrics,
            'computed_at' => $this->computed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Domains\Applications\Resources;

use App\Domains\Applications\Enums\ApplicationMonitoringAlertOperator;
use App\Domains\Applications\Enums\ApplicationMonitoringAlertSeverity;
use App\Domains\Applications\Enums\ApplicationMonitoringMetric;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationMonitoringAlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $metric = $this->metric instanceof ApplicationMonitoringMetric ? $this->metric : ApplicationMonitoringMetric::tryFrom((string) $this->metric);
        $operator = $this->operator instanceof ApplicationMonitoringAlertOperator ? $this->operator : ApplicationMonitoringAlertOperator::tryFrom((string) $this->operator);
        $severity = $this->severity instanceof ApplicationMonitoringAlertSeverity ? $this->severity : ApplicationMonitoringAlertSeverity::tryFrom((string) $this->severity);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'application_id' => $this->application_id,
            'name' => $this->name,
            'metric' => $metric?->value ?? $this->metric,
            'metric_label' => $metric?->label(),
            'operator' => $operator?->value ?? $this->operator,
            'operator_label' => $operator?->label(),
            'threshold' => $this->threshold,
            'severity' => $severity?->value ?? $this->severity,
            'severity_label' => $severity?->label(),
            'is_active' => (bool) $this->is_active,
            'cooldown_minutes' => $this->cooldown_minutes,
            'last_triggered_at' => $this->last_triggered_at,
            'message' => $this->message,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

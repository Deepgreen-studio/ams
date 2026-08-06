<?php

namespace App\Domains\Support\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportSlaPolicyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'priority' => $this->priority?->value ?? $this->priority,
            'priority_label' => $this->priority?->label(),
            'category' => $this->category?->value ?? $this->category,
            'category_label' => $this->category?->label(),
            'response_target_minutes' => $this->response_target_minutes,
            'resolution_target_minutes' => $this->resolution_target_minutes,
            'at_risk_percent' => $this->at_risk_percent,
            'business_hours_only' => (bool) $this->business_hours_only,
            'is_default' => (bool) $this->is_default,
            'is_active' => (bool) $this->is_active,
            'description' => $this->description,
            'company' => $this->whenLoaded('company', fn () => $this->company ? [
                'uuid' => $this->company->uuid,
                'company_name' => $this->company->company_name,
            ] : null),
            'calendar' => $this->whenLoaded('calendar', fn () => $this->calendar ? [
                'uuid' => $this->calendar->uuid,
                'name' => $this->calendar->name,
                'timezone' => $this->calendar->timezone,
            ] : null),
            'escalation_rules' => SupportSlaEscalationRuleResource::collection($this->whenLoaded('escalationRules')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

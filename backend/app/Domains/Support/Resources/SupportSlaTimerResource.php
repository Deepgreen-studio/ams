<?php

namespace App\Domains\Support\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportSlaTimerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $now = CarbonImmutable::now();
        $responseDue = $this->first_response_due_at ? CarbonImmutable::parse($this->first_response_due_at) : null;
        $resolutionDue = $this->resolution_due_at ? CarbonImmutable::parse($this->resolution_due_at) : null;

        return [
            'uuid' => $this->uuid,
            'ticket_number' => $this->ticket_number,
            'subject' => $this->subject,
            'priority' => $this->priority?->value ?? $this->priority,
            'status' => $this->status?->value ?? $this->status,
            'sla_status' => $this->sla_status?->value ?? $this->sla_status,
            'sla_status_label' => $this->sla_status?->label(),
            'escalation_level' => $this->escalation_level?->value ?? $this->escalation_level,
            'escalation_level_label' => $this->escalation_level?->label(),
            'first_response_due_at' => $this->first_response_due_at,
            'resolution_due_at' => $this->resolution_due_at,
            'first_response_at' => $this->first_response_at,
            'resolved_at' => $this->resolved_at,
            'response_breached_at' => $this->response_breached_at,
            'resolution_breached_at' => $this->resolution_breached_at,
            'response_remaining_seconds' => $this->first_response_at || ! $responseDue
                ? null
                : (int) $now->diffInSeconds($responseDue, false),
            'resolution_remaining_seconds' => $this->resolved_at || ! $resolutionDue
                ? null
                : (int) $now->diffInSeconds($resolutionDue, false),
            'company' => $this->whenLoaded('company', fn () => $this->company ? [
                'uuid' => $this->company->uuid,
                'company_name' => $this->company->company_name,
            ] : null),
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'uuid' => $this->assignee->uuid,
                'full_name' => $this->assignee->full_name,
            ] : null),
            'policy' => $this->whenLoaded('slaPolicy', fn () => $this->slaPolicy ? [
                'uuid' => $this->slaPolicy->uuid,
                'name' => $this->slaPolicy->name,
            ] : null),
        ];
    }
}

<?php

namespace App\Domains\Support\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportSlaEscalationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'level' => $this->level?->value ?? $this->level,
            'level_label' => $this->level?->label(),
            'trigger' => $this->trigger?->value ?? $this->trigger,
            'trigger_label' => $this->trigger?->label(),
            'metric' => $this->metric?->value ?? $this->metric,
            'metric_label' => $this->metric?->label(),
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'triggered_at' => $this->triggered_at,
            'acknowledged_at' => $this->acknowledged_at,
            'resolved_at' => $this->resolved_at,
            'notes' => $this->notes,
            'ticket' => $this->whenLoaded('ticket', fn () => $this->ticket ? [
                'uuid' => $this->ticket->uuid,
                'ticket_number' => $this->ticket->ticket_number,
                'subject' => $this->ticket->subject,
                'priority' => $this->ticket->priority?->value ?? $this->ticket->priority,
                'status' => $this->ticket->status?->value ?? $this->ticket->status,
                'sla_status' => $this->ticket->sla_status?->value ?? $this->ticket->sla_status,
                'first_response_due_at' => $this->ticket->first_response_due_at,
                'resolution_due_at' => $this->ticket->resolution_due_at,
                'company' => $this->ticket->relationLoaded('company') && $this->ticket->company ? [
                    'uuid' => $this->ticket->company->uuid,
                    'company_name' => $this->ticket->company->company_name,
                ] : null,
                'assignee' => $this->ticket->relationLoaded('assignee') && $this->ticket->assignee ? [
                    'uuid' => $this->ticket->assignee->uuid,
                    'full_name' => $this->ticket->assignee->full_name,
                ] : null,
            ] : null),
            'policy' => $this->whenLoaded('policy', fn () => $this->policy ? [
                'uuid' => $this->policy->uuid,
                'name' => $this->policy->name,
            ] : null),
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'uuid' => $this->assignee->uuid,
                'full_name' => $this->assignee->full_name,
            ] : null),
            'acknowledger' => $this->whenLoaded('acknowledger', fn () => $this->acknowledger ? [
                'uuid' => $this->acknowledger->uuid,
                'full_name' => $this->acknowledger->full_name,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}

<?php

namespace App\Domains\Support\Resources;

use App\Domains\Support\Enums\SupportTicketStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof SupportTicketStatus
            ? $this->status
            : SupportTicketStatus::tryFrom((string) $this->status);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'ticket_number' => $this->ticket_number,
            'subject' => $this->subject,
            'description' => $this->description,
            'priority' => $this->priority?->value ?? $this->priority,
            'priority_label' => $this->priority?->label(),
            'priority_rank' => $this->priority?->rank(),
            'category' => $this->category?->value ?? $this->category,
            'category_label' => $this->category?->label(),
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'allowed_transitions' => array_map(
                fn (SupportTicketStatus $item) => [
                    'value' => $item->value,
                    'label' => $item->label(),
                ],
                $status?->allowedTransitions() ?? []
            ),
            'source' => $this->source?->value ?? $this->source,
            'source_label' => $this->source?->label(),
            'involves_personal_data' => (bool) $this->involves_personal_data,
            'compliance_routed_at' => $this->compliance_routed_at,
            'privacy_request' => $this->whenLoaded('privacyRequest', fn () => $this->privacyRequest ? [
                'id' => $this->privacyRequest->id,
                'uuid' => $this->privacyRequest->uuid,
                'request_number' => $this->privacyRequest->request_number,
                'request_type' => $this->privacyRequest->request_type?->value ?? $this->privacyRequest->request_type,
                'status' => $this->privacyRequest->status?->value ?? $this->privacyRequest->status,
            ] : null),
            'assignment_type' => $this->assignment_type?->value ?? $this->assignment_type,
            'assignment_type_label' => $this->assignment_type?->label(),
            'assigned_at' => $this->assigned_at,
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company?->id,
                'uuid' => $this->company?->uuid,
                'company_name' => $this->company?->company_name,
                'email' => $this->company?->email ?? null,
            ]),
            'customer' => $this->whenLoaded('customer', function () {
                if (! $this->customer) {
                    return null;
                }

                $displayName = trim(($this->customer->first_name ?? '').' '.($this->customer->last_name ?? ''));
                if ($displayName === '') {
                    $displayName = $this->customer->company_name;
                }

                return [
                    'id' => $this->customer->id,
                    'uuid' => $this->customer->uuid,
                    'display_name' => $displayName ?: $this->customer->email,
                    'email' => $this->customer->email,
                    'phone' => $this->customer->phone ?? null,
                    'customer_type' => $this->customer->customer_type?->value ?? $this->customer->customer_type,
                ];
            }),
            'application' => $this->whenLoaded('application', fn () => $this->application ? [
                'id' => $this->application->id,
                'uuid' => $this->application->uuid,
                'name' => $this->application->name,
                'slug' => $this->application->slug,
                'platform' => $this->application->platform?->value ?? $this->application->platform,
                'status' => $this->application->status?->value ?? $this->application->status,
            ] : null),
            'department' => $this->whenLoaded('department', fn () => $this->department ? [
                'id' => $this->department->id,
                'uuid' => $this->department->uuid,
                'name' => $this->department->name,
            ] : null),
            'team' => $this->whenLoaded('team', fn () => $this->team ? [
                'id' => $this->team->id,
                'uuid' => $this->team->uuid,
                'name' => $this->team->name,
            ] : null),
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'id' => $this->assignee->id,
                'uuid' => $this->assignee->uuid,
                'full_name' => $this->assignee->full_name,
                'email' => $this->assignee->email,
            ] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'uuid' => $this->creator->uuid,
                'full_name' => $this->creator->full_name,
                'email' => $this->creator->email,
            ] : null),
            'updater' => $this->whenLoaded('updater', fn () => $this->updater ? [
                'uuid' => $this->updater->uuid,
                'full_name' => $this->updater->full_name,
                'email' => $this->updater->email,
            ] : null),
            'closed_at' => $this->closed_at,
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
            'sla_paused_at' => $this->sla_paused_at,
            'sla_paused_seconds' => $this->sla_paused_seconds,
            'sla_policy' => $this->whenLoaded('slaPolicy', fn () => $this->slaPolicy ? [
                'uuid' => $this->slaPolicy->uuid,
                'name' => $this->slaPolicy->name,
                'code' => $this->slaPolicy->code,
                'response_target_minutes' => $this->slaPolicy->response_target_minutes,
                'resolution_target_minutes' => $this->slaPolicy->resolution_target_minutes,
                'at_risk_percent' => $this->slaPolicy->at_risk_percent,
                'business_hours_only' => (bool) $this->slaPolicy->business_hours_only,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

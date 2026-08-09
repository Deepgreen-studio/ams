<?php

namespace App\Domains\Compliance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrivacyRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'uuid' => $this->company->uuid,
                    'company_name' => $this->company->company_name,
                    'status' => $this->company->status?->value ?? $this->company->status ?? null,
                ];
            }),
            'request_number' => $this->request_number,
            'request_type' => $this->request_type?->value ?? $this->request_type,
            'request_type_label' => $this->request_type?->label(),
            'requester_name' => $this->requester_name,
            'requester_email' => $this->requester_email,
            'requester_phone' => $this->requester_phone,
            'customer_id' => $this->customer_id,
            'support_ticket_id' => $this->support_ticket_id,
            'support_ticket' => $this->whenLoaded('supportTicket', function () {
                return $this->supportTicket ? [
                    'id' => $this->supportTicket->id,
                    'uuid' => $this->supportTicket->uuid,
                    'ticket_number' => $this->supportTicket->ticket_number,
                    'subject' => $this->supportTicket->subject,
                    'status' => $this->supportTicket->status?->value ?? $this->supportTicket->status,
                    'source' => $this->supportTicket->source?->value ?? $this->supportTicket->source,
                ] : null;
            }),
            'customer' => $this->whenLoaded('customer', function () {
                if (! $this->customer) {
                    return null;
                }

                return [
                    'id' => $this->customer->id,
                    'uuid' => $this->customer->uuid,
                    'first_name' => $this->customer->first_name,
                    'last_name' => $this->customer->last_name,
                    'company_name' => $this->customer->company_name,
                    'email' => $this->customer->email,
                    'phone' => $this->customer->phone,
                    'display_name' => $this->customer->display_name,
                ];
            }),
            'description' => $this->description,
            'identity_verification_status' => $this->identity_verification_status?->value ?? $this->identity_verification_status,
            'identity_verification_status_label' => $this->identity_verification_status?->label(),
            'identity_verified_at' => $this->identity_verified_at,
            'identity_verification_notes' => $this->identity_verification_notes,
            'identity_verifier' => $this->whenLoaded('identityVerifier', function () {
                return $this->identityVerifier ? [
                    'id' => $this->identityVerifier->id,
                    'uuid' => $this->identityVerifier->uuid,
                    'full_name' => $this->identityVerifier->full_name,
                    'email' => $this->identityVerifier->email,
                ] : null;
            }),
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'assigned_to' => $this->assigned_to,
            'assignee' => $this->whenLoaded('assignee', function () {
                return $this->assignee ? [
                    'id' => $this->assignee->id,
                    'uuid' => $this->assignee->uuid,
                    'full_name' => $this->assignee->full_name,
                    'email' => $this->assignee->email,
                ] : null;
            }),
            'due_date' => optional($this->due_date)?->toDateString(),
            'completed_at' => $this->completed_at,
            'decision' => $this->decision?->value ?? $this->decision,
            'decision_label' => $this->decision?->label(),
            'decision_notes' => $this->decision_notes,
            'decision_at' => $this->decision_at,
            'decision_maker' => $this->whenLoaded('decisionMaker', function () {
                return $this->decisionMaker ? [
                    'id' => $this->decisionMaker->id,
                    'uuid' => $this->decisionMaker->uuid,
                    'full_name' => $this->decisionMaker->full_name,
                    'email' => $this->decisionMaker->email,
                ] : null;
            }),
            'export_payload' => $this->export_payload,
            'export_file_path' => $this->export_file_path,
            'export_generated_at' => $this->export_generated_at,
            'has_export' => filled($this->export_generated_at),
            'deletion_confirmed_at' => $this->deletion_confirmed_at,
            'requires_export' => $this->request_type?->requiresExport() ?? false,
            'requires_deletion' => $this->request_type?->requiresDeletion() ?? false,
            'allowed_transitions' => array_map(
                fn ($status) => $status->value,
                $this->status?->allowedTransitions() ?? []
            ),
            'creator' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id' => $this->creator->id,
                    'uuid' => $this->creator->uuid,
                    'full_name' => $this->creator->full_name,
                    'email' => $this->creator->email,
                ] : null;
            }),
            'updater' => $this->whenLoaded('updater', function () {
                return $this->updater ? [
                    'id' => $this->updater->id,
                    'uuid' => $this->updater->uuid,
                    'full_name' => $this->updater->full_name,
                    'email' => $this->updater->email,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

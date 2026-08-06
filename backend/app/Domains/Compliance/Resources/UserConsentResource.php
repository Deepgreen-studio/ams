<?php

namespace App\Domains\Compliance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserConsentResource extends JsonResource
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
                ];
            }),
            'consent_type_id' => $this->consent_type_id,
            'consent_type' => $this->whenLoaded('consentType', function () {
                return [
                    'id' => $this->consentType->id,
                    'uuid' => $this->consentType->uuid,
                    'code' => $this->consentType->code,
                    'name' => $this->consentType->name,
                    'channel' => $this->consentType->channel?->value ?? $this->consentType->channel,
                    'channel_label' => $this->consentType->channel?->label(),
                    'current_version' => $this->consentType->current_version,
                ];
            }),
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'uuid' => $this->user->uuid,
                    'full_name' => $this->user->full_name,
                    'email' => $this->user->email,
                ] : null;
            }),
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', function () {
                return $this->customer ? [
                    'id' => $this->customer->id,
                    'uuid' => $this->customer->uuid,
                    'display_name' => $this->customer->display_name,
                    'email' => $this->customer->email,
                ] : null;
            }),
            'subject_email' => $this->subject_email,
            'subject_name' => $this->subject_name,
            'consent_version' => $this->consent_version,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'granted' => (bool) $this->granted,
            'consented_at' => $this->consented_at,
            'withdrawn_at' => $this->withdrawn_at,
            'ip_address' => $this->ip_address,
            'device' => $this->device,
            'user_agent' => $this->user_agent,
            'source' => $this->source?->value ?? $this->source,
            'source_label' => $this->source?->label(),
            'notes' => $this->notes,
            'creator' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id' => $this->creator->id,
                    'uuid' => $this->creator->uuid,
                    'full_name' => $this->creator->full_name,
                    'email' => $this->creator->email,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

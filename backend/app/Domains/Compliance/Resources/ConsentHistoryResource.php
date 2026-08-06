<?php

namespace App\Domains\Compliance\Resources;

use App\Domains\Compliance\Enums\ConsentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsentHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $from = $this->from_status ? ConsentStatus::tryFrom((string) $this->from_status) : null;
        $to = $this->to_status ? ConsentStatus::tryFrom((string) $this->to_status) : null;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_consent_id' => $this->user_consent_id,
            'consent_type_id' => $this->consent_type_id,
            'consent_type' => $this->whenLoaded('consentType', function () {
                return [
                    'id' => $this->consentType->id,
                    'uuid' => $this->consentType->uuid,
                    'code' => $this->consentType->code,
                    'name' => $this->consentType->name,
                    'channel' => $this->consentType->channel?->value ?? $this->consentType->channel,
                    'channel_label' => $this->consentType->channel?->label(),
                ];
            }),
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'uuid' => $this->company->uuid,
                    'company_name' => $this->company->company_name,
                ];
            }),
            'user_consent' => $this->whenLoaded('userConsent', function () {
                return $this->userConsent ? [
                    'id' => $this->userConsent->id,
                    'uuid' => $this->userConsent->uuid,
                    'subject_email' => $this->userConsent->subject_email,
                    'subject_name' => $this->userConsent->subject_name,
                    'status' => $this->userConsent->status?->value ?? $this->userConsent->status,
                    'granted' => (bool) $this->userConsent->granted,
                ] : null;
            }),
            'action' => $this->action?->value ?? $this->action,
            'action_label' => $this->action?->label(),
            'from_status' => $this->from_status,
            'from_status_label' => $from?->label(),
            'to_status' => $this->to_status,
            'to_status_label' => $to?->label(),
            'from_version' => $this->from_version,
            'to_version' => $this->to_version,
            'from_granted' => $this->from_granted,
            'to_granted' => $this->to_granted,
            'ip_address' => $this->ip_address,
            'device' => $this->device,
            'source' => $this->source,
            'comments' => $this->comments,
            'metadata' => $this->metadata,
            'actor' => $this->whenLoaded('actor', function () {
                return $this->actor ? [
                    'id' => $this->actor->id,
                    'uuid' => $this->actor->uuid,
                    'full_name' => $this->actor->full_name,
                    'email' => $this->actor->email,
                ] : null;
            }),
            'created_at' => $this->created_at,
        ];
    }
}

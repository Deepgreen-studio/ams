<?php

namespace App\Domains\Compliance\Resources;

use App\Domains\Compliance\Enums\PrivacyRequestStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrivacyRequestLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $from = $this->from_status ? PrivacyRequestStatus::tryFrom((string) $this->from_status) : null;
        $to = $this->to_status ? PrivacyRequestStatus::tryFrom((string) $this->to_status) : null;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'privacy_request_id' => $this->privacy_request_id,
            'from_status' => $this->from_status,
            'from_status_label' => $from?->label(),
            'to_status' => $this->to_status,
            'to_status_label' => $to?->label(),
            'action' => $this->action?->value ?? $this->action,
            'action_label' => $this->action?->label(),
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

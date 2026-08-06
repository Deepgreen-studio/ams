<?php

namespace App\Domains\Compliance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PolicyApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'policy_id' => $this->policy_id,
            'policy' => $this->whenLoaded('policy', function () {
                if (! $this->policy) {
                    return null;
                }

                return [
                    'uuid' => $this->policy->uuid,
                    'policy_number' => $this->policy->policy_number,
                    'title' => $this->policy->title,
                    'status' => $this->policy->status?->value ?? $this->policy->status,
                    'policy_type' => $this->policy->policy_type?->value ?? $this->policy->policy_type,
                    'current_version' => $this->policy->current_version,
                    'company' => $this->policy->relationLoaded('company') && $this->policy->company
                        ? [
                            'uuid' => $this->policy->company->uuid,
                            'company_name' => $this->policy->company->company_name,
                        ]
                        : null,
                ];
            }),
            'policy_version_id' => $this->policy_version_id,
            'version' => $this->whenLoaded('version', function () {
                if (! $this->version) {
                    return null;
                }

                return [
                    'uuid' => $this->version->uuid,
                    'version' => $this->version->version,
                    'status' => $this->version->status?->value ?? $this->version->status,
                    'title' => $this->version->title,
                ];
            }),
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'comments' => $this->comments,
            'requested_at' => $this->requested_at,
            'decided_at' => $this->decided_at,
            'requester' => $this->whenLoaded('requester', function () {
                return $this->requester ? [
                    'uuid' => $this->requester->uuid,
                    'full_name' => $this->requester->full_name,
                    'email' => $this->requester->email,
                ] : null;
            }),
            'reviewer' => $this->whenLoaded('reviewer', function () {
                return $this->reviewer ? [
                    'uuid' => $this->reviewer->uuid,
                    'full_name' => $this->reviewer->full_name,
                    'email' => $this->reviewer->email,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

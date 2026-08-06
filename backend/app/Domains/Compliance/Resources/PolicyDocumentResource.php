<?php

namespace App\Domains\Compliance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PolicyDocumentResource extends JsonResource
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
            'policy_number' => $this->policy_number,
            'title' => $this->title,
            'slug' => $this->slug,
            'policy_type' => $this->policy_type?->value ?? $this->policy_type,
            'policy_type_label' => $this->policy_type?->label(),
            'description' => $this->description,
            'body' => $this->body,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'current_version' => $this->current_version,
            'content_id' => $this->content_id,
            'content' => $this->whenLoaded('content', function () {
                if (! $this->content) {
                    return null;
                }

                return [
                    'id' => $this->content->id,
                    'uuid' => $this->content->uuid,
                    'title' => $this->content->title,
                    'slug' => $this->content->slug,
                    'version' => $this->content->version,
                ];
            }),
            'effective_at' => $this->effective_at,
            'expires_at' => $this->expires_at,
            'review_due_at' => optional($this->review_due_at)?->toDateString(),
            'published_at' => $this->published_at,
            'allowed_transitions' => array_map(
                fn ($status) => $status->value,
                $this->status?->allowedTransitions() ?? []
            ),
            'assignee' => $this->whenLoaded('assignee', function () {
                return $this->assignee ? [
                    'id' => $this->assignee->id,
                    'uuid' => $this->assignee->uuid,
                    'full_name' => $this->assignee->full_name,
                    'email' => $this->assignee->email,
                ] : null;
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id' => $this->creator->id,
                    'uuid' => $this->creator->uuid,
                    'full_name' => $this->creator->full_name,
                    'email' => $this->creator->email,
                ] : null;
            }),
            'versions' => PolicyVersionResource::collection($this->whenLoaded('versions')),
            'approvals' => PolicyApprovalResource::collection($this->whenLoaded('approvals')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

<?php

namespace App\Domains\Notifications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationTemplateApprovalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'comments' => $this->comments,
            'requested_at' => $this->requested_at,
            'decided_at' => $this->decided_at,
            'template' => $this->whenLoaded('template', fn () => [
                'uuid' => $this->template?->uuid,
                'name' => $this->template?->name,
                'channel' => $this->template?->channel?->value ?? $this->template?->channel,
                'locale' => $this->template?->locale,
                'event_key' => $this->template?->event_key?->value ?? $this->template?->event_key,
                'workflow_status' => $this->template?->workflow_status?->value ?? $this->template?->workflow_status,
            ]),
            'version' => $this->whenLoaded('version', fn () => [
                'uuid' => $this->version?->uuid,
                'version' => $this->version?->version,
                'status' => $this->version?->status?->value ?? $this->version?->status,
            ]),
            'requester' => $this->whenLoaded('requester', fn () => [
                'uuid' => $this->requester?->uuid,
                'full_name' => $this->requester?->full_name,
                'email' => $this->requester?->email,
            ]),
            'reviewer' => $this->whenLoaded('reviewer', fn () => [
                'uuid' => $this->reviewer?->uuid,
                'full_name' => $this->reviewer?->full_name,
                'email' => $this->reviewer?->email,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}

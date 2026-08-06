<?php

namespace App\Domains\Notifications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company?->uuid,
            'company' => $this->whenLoaded('company', fn () => [
                'uuid' => $this->company?->uuid,
                'company_name' => $this->company?->company_name,
            ]),
            'event_key' => $this->event_key?->value ?? $this->event_key,
            'event_label' => $this->event_key?->label(),
            'channel' => $this->channel?->value ?? $this->channel,
            'channel_label' => $this->channel?->label(),
            'locale' => $this->locale ?? 'en',
            'name' => $this->name,
            'subject' => $this->subject,
            'body' => $this->body,
            'available_variables' => $this->available_variables ?? [],
            'is_active' => (bool) $this->is_active,
            'is_system' => (bool) $this->is_system,
            'priority' => $this->priority?->value ?? $this->priority ?? 'normal',
            'priority_label' => $this->priority?->label() ?? 'Normal',
            'workflow_status' => $this->workflow_status?->value ?? $this->workflow_status ?? 'draft',
            'workflow_status_label' => $this->workflow_status?->label() ?? 'Draft',
            'current_version' => (int) ($this->current_version ?? 1),
            'change_summary' => $this->change_summary,
            'published_at' => $this->published_at,
            'creator' => $this->whenLoaded('creator', fn () => [
                'uuid' => $this->creator?->uuid,
                'full_name' => $this->creator?->full_name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Domains\Notifications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationTemplateVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'version' => (int) $this->version,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'name' => $this->name,
            'channel' => $this->channel?->value ?? $this->channel,
            'locale' => $this->locale,
            'event_key' => $this->event_key?->value ?? $this->event_key,
            'subject' => $this->subject,
            'body' => $this->body,
            'available_variables' => $this->available_variables ?? [],
            'priority' => $this->priority?->value ?? $this->priority,
            'snapshot' => $this->snapshot,
            'reason' => $this->reason,
            'is_restore' => (bool) $this->is_restore,
            'restored_from_version' => $this->restored_from_version,
            'creator' => $this->whenLoaded('creator', fn () => [
                'uuid' => $this->creator?->uuid,
                'full_name' => $this->creator?->full_name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}

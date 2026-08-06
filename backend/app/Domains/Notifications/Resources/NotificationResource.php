<?php

namespace App\Domains\Notifications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'uuid' => $this->user?->uuid,
                'full_name' => $this->user?->full_name,
                'email' => $this->user?->email,
            ]),
            'channel' => $this->channel?->value ?? $this->channel,
            'channel_label' => $this->channel?->label(),
            'template' => $this->template,
            'event_key' => $this->event_key,
            'title' => $this->title,
            'message' => $this->message,
            'body' => $this->message,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'priority' => $this->priority?->value ?? $this->priority,
            'priority_label' => $this->priority?->label(),
            'data' => $this->data ?? [],
            'scheduled_at' => $this->scheduled_at,
            'sent_at' => $this->sent_at,
            'read_at' => $this->read_at,
            'is_read' => $this->read_at !== null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

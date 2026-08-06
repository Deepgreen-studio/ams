<?php

namespace App\Domains\Notifications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationDeliveryLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'notification_id' => $this->notification?->uuid,
            'company_id' => $this->company?->uuid,
            'event_key' => $this->event_key?->value ?? $this->event_key,
            'event_label' => $this->event_key?->label(),
            'channel' => $this->channel?->value ?? $this->channel,
            'channel_label' => $this->channel?->label(),
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'recipient' => $this->recipient,
            'subject' => $this->subject,
            'body_preview' => $this->body_preview,
            'error_message' => $this->error_message,
            'payload' => $this->payload,
            'notifiable' => $this->whenLoaded('notifiable', function () {
                if (! $this->notifiable) {
                    return null;
                }

                return [
                    'id' => $this->notifiable->id ?? null,
                    'uuid' => $this->notifiable->uuid ?? null,
                    'full_name' => $this->notifiable->full_name ?? $this->notifiable->name ?? null,
                    'email' => $this->notifiable->email ?? null,
                ];
            }),
            'queued_at' => $this->queued_at,
            'sent_at' => $this->sent_at,
            'failed_at' => $this->failed_at,
            'created_at' => $this->created_at,
        ];
    }
}

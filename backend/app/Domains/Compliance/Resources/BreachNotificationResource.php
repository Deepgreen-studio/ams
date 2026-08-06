<?php

namespace App\Domains\Compliance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BreachNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'data_breach_id' => $this->data_breach_id,
            'data_breach' => $this->whenLoaded('dataBreach', function () {
                if (! $this->dataBreach) {
                    return null;
                }

                return [
                    'id' => $this->dataBreach->id,
                    'uuid' => $this->dataBreach->uuid,
                    'breach_number' => $this->dataBreach->breach_number,
                    'title' => $this->dataBreach->title,
                    'status' => $this->dataBreach->status?->value ?? $this->dataBreach->status,
                    'severity' => $this->dataBreach->severity?->value ?? $this->dataBreach->severity,
                    'company' => $this->dataBreach->relationLoaded('company') && $this->dataBreach->company
                        ? [
                            'uuid' => $this->dataBreach->company->uuid,
                            'company_name' => $this->dataBreach->company->company_name,
                        ]
                        : null,
                ];
            }),
            'notification_type' => $this->notification_type?->value ?? $this->notification_type,
            'notification_type_label' => $this->notification_type?->label(),
            'channel' => $this->channel?->value ?? $this->channel,
            'channel_label' => $this->channel?->label(),
            'recipient' => $this->recipient,
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'sent_at' => $this->sent_at,
            'acknowledged_at' => $this->acknowledged_at,
            'sent_by' => $this->sent_by,
            'sender' => $this->whenLoaded('sender', function () {
                return $this->sender ? [
                    'id' => $this->sender->id,
                    'uuid' => $this->sender->uuid,
                    'full_name' => $this->sender->full_name,
                    'email' => $this->sender->email,
                ] : null;
            }),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

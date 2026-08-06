<?php

namespace App\Domains\Integrations\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebhookLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'webhook_id' => $this->webhook_id,
            'webhook' => $this->whenLoaded('webhook', fn () => $this->webhook ? [
                'id' => $this->webhook->id,
                'uuid' => $this->webhook->uuid,
                'name' => $this->webhook->name,
                'slug' => $this->webhook->slug,
                'direction' => $this->webhook->direction?->value ?? $this->webhook->direction,
                'status' => $this->webhook->status?->value ?? $this->webhook->status,
            ] : null),
            'event' => $this->whenLoaded('event', fn () => $this->event ? [
                'id' => $this->event->id,
                'uuid' => $this->event->uuid,
                'name' => $this->event->name,
                'label' => $this->event->label,
            ] : null),
            'direction' => $this->direction?->value ?? $this->direction,
            'event_name' => $this->event_name,
            'status' => $this->status?->value ?? $this->status,
            'method' => $this->method,
            'url' => $this->url,
            'request_headers' => $this->request_headers,
            'request_body' => $this->request_body,
            'response_status' => $this->response_status,
            'response_headers' => $this->response_headers,
            'response_body' => $this->response_body,
            'duration_ms' => $this->duration_ms,
            'attempts' => $this->attempts,
            'max_attempts' => $this->max_attempts,
            'next_retry_at' => $this->next_retry_at,
            'error_message' => $this->error_message,
            'is_test' => $this->is_test,
            'actor' => $this->whenLoaded('actor', fn () => $this->actor ? [
                'id' => $this->actor->id,
                'uuid' => $this->actor->uuid,
                'full_name' => $this->actor->full_name,
                'email' => $this->actor->email,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Domains\Integrations\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebhookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'uuid' => $this->company->uuid,
                'company_name' => $this->company->company_name,
            ]),
            'integration_id' => $this->integration_id,
            'integration' => $this->whenLoaded('integration', fn () => $this->integration ? [
                'id' => $this->integration->id,
                'uuid' => $this->integration->uuid,
                'name' => $this->integration->name,
                'slug' => $this->integration->slug,
            ] : null),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'direction' => $this->direction?->value ?? $this->direction,
            'status' => $this->status?->value ?? $this->status,
            'url' => $this->url,
            'has_secret' => $this->has_secret,
            'signature_algorithm' => $this->signature_algorithm?->value ?? $this->signature_algorithm,
            'signature_header' => $this->signature_header,
            'subscribed_events' => $this->subscribed_events ?? [],
            'headers' => $this->headers ?? [],
            'timeout' => $this->timeout,
            'retry_attempts' => $this->retry_attempts,
            'retry_delay_seconds' => $this->retry_delay_seconds,
            'verify_ssl' => $this->verify_ssl,
            'incoming_url' => ($this->direction?->value ?? $this->direction) === 'incoming'
                ? url('/api/v1/webhooks/incoming/'.$this->uuid)
                : null,
            'last_triggered_at' => $this->last_triggered_at,
            'last_success_at' => $this->last_success_at,
            'last_failure_at' => $this->last_failure_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

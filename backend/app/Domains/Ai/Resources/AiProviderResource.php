<?php

namespace App\Domains\Ai\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiProviderResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'driver' => $this->driver?->value ?? $this->driver,
            'driver_label' => $this->driver?->label(),
            'status' => $this->status,
            'base_url' => $this->base_url,
            'default_model' => $this->default_model,
            'embedding_model' => $this->embedding_model,
            'authentication_type' => $this->authentication_type,
            'has_credentials' => ! empty($this->credentials),
            'config' => $this->config,
            'health_status' => $this->health_status,
            'last_health_check_at' => $this->last_health_check_at,
            'timeout_seconds' => $this->timeout_seconds,
            'retry_attempts' => $this->retry_attempts,
            'is_default' => (bool) $this->is_default,
            'is_enabled' => (bool) $this->is_enabled,
            'creator' => $this->whenLoaded('creator', fn () => [
                'uuid' => $this->creator?->uuid,
                'full_name' => $this->creator?->full_name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

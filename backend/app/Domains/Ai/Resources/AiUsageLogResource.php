<?php

namespace App\Domains\Ai\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiUsageLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'feature' => $this->feature?->value ?? $this->feature,
            'feature_label' => $this->feature?->label(),
            'operation' => $this->operation,
            'driver' => $this->driver,
            'model' => $this->model,
            'tokens_in' => $this->tokens_in,
            'tokens_out' => $this->tokens_out,
            'latency_ms' => $this->latency_ms,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'cost_estimate' => $this->cost_estimate,
            'request_id' => $this->request_id,
            'meta' => $this->meta,
            'user' => $this->whenLoaded('user', fn () => [
                'uuid' => $this->user?->uuid,
                'full_name' => $this->user?->full_name,
                'email' => $this->user?->email,
            ]),
            'provider' => $this->whenLoaded('provider', fn () => [
                'uuid' => $this->provider?->uuid,
                'name' => $this->provider?->name,
                'driver' => $this->provider?->driver?->value ?? $this->provider?->driver,
            ]),
            'conversation' => $this->whenLoaded('conversation', fn () => [
                'uuid' => $this->conversation?->uuid,
                'title' => $this->conversation?->title,
                'feature' => $this->conversation?->feature?->value ?? $this->conversation?->feature,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}

<?php

namespace App\Domains\Ai\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company_id,
            'feature' => $this->feature?->value ?? $this->feature,
            'feature_label' => $this->feature?->label(),
            'context_type' => $this->context_type,
            'context_id' => $this->context_id,
            'title' => $this->title,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'messages_count' => $this->when(isset($this->messages_count), $this->messages_count),
            'user' => $this->whenLoaded('user', fn () => [
                'uuid' => $this->user?->uuid,
                'full_name' => $this->user?->full_name,
                'email' => $this->user?->email,
            ]),
            'provider' => $this->whenLoaded('provider', fn () => [
                'uuid' => $this->provider?->uuid,
                'name' => $this->provider?->name,
                'driver' => $this->provider?->driver?->value ?? $this->provider?->driver,
                'slug' => $this->provider?->slug,
            ]),
            'prompt' => $this->whenLoaded('prompt', fn () => [
                'uuid' => $this->prompt?->uuid,
                'key' => $this->prompt?->key,
                'name' => $this->prompt?->name,
                'feature' => $this->prompt?->feature?->value ?? $this->prompt?->feature,
            ]),
            'messages' => $this->whenLoaded('messages', fn () => AiMessageResource::collection($this->messages)->resolve()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

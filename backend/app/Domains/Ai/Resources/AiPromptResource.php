<?php

namespace App\Domains\Ai\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiPromptResource extends JsonResource
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
            'key' => $this->key,
            'name' => $this->name,
            'feature' => $this->feature?->value ?? $this->feature,
            'feature_label' => $this->feature?->label(),
            'system_prompt' => $this->system_prompt,
            'user_template' => $this->user_template,
            'model_override' => $this->model_override,
            'temperature' => $this->temperature,
            'max_tokens' => $this->max_tokens,
            'output_schema' => $this->output_schema,
            'version' => $this->version,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'metadata' => $this->metadata,
            'creator' => $this->whenLoaded('creator', fn () => [
                'uuid' => $this->creator?->uuid,
                'full_name' => $this->creator?->full_name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

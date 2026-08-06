<?php

namespace App\Domains\Integrations\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataMappingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'uuid' => $this->company->uuid,
                'company_name' => $this->company->company_name,
            ]),
            'integration' => $this->whenLoaded('integration', fn () => $this->integration ? [
                'id' => $this->integration->id,
                'uuid' => $this->integration->uuid,
                'name' => $this->integration->name,
                'slug' => $this->integration->slug,
                'status' => $this->integration->status?->value ?? $this->integration->status,
            ] : null),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'direction' => $this->direction?->value ?? $this->direction,
            'status' => $this->status?->value ?? $this->status,
            'source_entity' => $this->source_entity,
            'target_entity' => $this->target_entity,
            'version' => $this->version,
            'is_active' => $this->is_active,
            'external_schema' => $this->external_schema ?? [],
            'sample_payload' => $this->sample_payload ?? [],
            'options' => $this->options ?? [],
            'fields_count' => $this->whenCounted('fields'),
            'fields' => DataMappingFieldResource::collection($this->whenLoaded('fields')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

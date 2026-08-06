<?php

namespace App\Domains\Integrations\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataMappingFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'external_field' => $this->external_field,
            'internal_field' => $this->internal_field,
            'transform_type' => $this->transform_type?->value ?? $this->transform_type,
            'transform_config' => $this->transform_config ?? [],
            'is_required' => $this->is_required,
            'default_value' => $this->default_value,
            'custom_rules' => $this->custom_rules ?? [],
            'sort_order' => $this->sort_order,
            'is_enabled' => $this->is_enabled,
            'notes' => $this->notes,
        ];
    }
}

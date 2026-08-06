<?php

namespace App\Domains\Compliance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsentTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'channel' => $this->channel?->value ?? $this->channel,
            'channel_label' => $this->channel?->label(),
            'current_version' => $this->current_version,
            'is_required' => (bool) $this->is_required,
            'is_active' => (bool) $this->is_active,
            'sort_order' => $this->sort_order,
            'is_platform_default' => $this->company_id === null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

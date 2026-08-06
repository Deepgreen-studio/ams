<?php

namespace App\Domains\Integrations\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SyncConfigResource extends JsonResource
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
                'base_url' => $this->integration->base_url ?? null,
            ] : null),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'direction' => $this->direction?->value ?? $this->direction,
            'default_mode' => $this->default_mode?->value ?? $this->default_mode,
            'trigger_type' => $this->trigger_type?->value ?? $this->trigger_type,
            'schedule_cron' => $this->schedule_cron,
            'is_enabled' => $this->is_enabled,
            'source_path' => $this->source_path,
            'target_path' => $this->target_path,
            'entity_type' => $this->entity_type,
            'conflict_strategy' => $this->conflict_strategy?->value ?? $this->conflict_strategy,
            'batch_size' => $this->batch_size,
            'cursor_field' => $this->cursor_field,
            'cursor_value' => $this->cursor_value,
            'field_mapping' => $this->field_mapping ?? [],
            'filters' => $this->filters ?? [],
            'options' => $this->options ?? [],
            'last_synced_at' => $this->last_synced_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Domains\Analytics\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsWidgetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'analytics_dashboard_id' => $this->analytics_dashboard_id,
            'name' => $this->name,
            'key' => $this->key,
            'type' => $this->type?->value ?? $this->type,
            'category' => $this->category?->value ?? $this->category,
            'data_source' => $this->data_source,
            'query_config' => $this->query_config,
            'visualization_config' => $this->visualization_config,
            'position_x' => $this->position_x,
            'position_y' => $this->position_y,
            'width' => $this->width,
            'height' => $this->height,
            'sort_order' => $this->sort_order,
            'refresh_interval_seconds' => $this->refresh_interval_seconds,
            'is_visible' => (bool) $this->is_visible,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

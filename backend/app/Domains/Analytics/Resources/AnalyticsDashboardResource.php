<?php

namespace App\Domains\Analytics\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsDashboardResource extends JsonResource
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
            'company' => $this->whenLoaded('company', function () {
                return $this->company ? [
                    'id' => $this->company->id,
                    'uuid' => $this->company->uuid,
                    'company_name' => $this->company->company_name,
                ] : null;
            }),
            'owner_id' => $this->owner_id,
            'owner' => $this->whenLoaded('owner', function () {
                return $this->owner ? [
                    'id' => $this->owner->id,
                    'uuid' => $this->owner->uuid,
                    'full_name' => $this->owner->full_name,
                    'email' => $this->owner->email,
                ] : null;
            }),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'kind' => $this->kind?->value ?? $this->kind,
            'category' => $this->category?->value ?? $this->category,
            'status' => $this->status?->value ?? $this->status,
            'visibility' => $this->visibility?->value ?? $this->visibility,
            'layout' => $this->layout,
            'filters' => $this->filters,
            'settings' => $this->settings,
            'is_default' => (bool) $this->is_default,
            'is_system' => (bool) $this->is_system,
            'is_shared' => (bool) $this->is_shared,
            'is_template' => (bool) $this->is_template,
            'template_source_id' => $this->template_source_id,
            'sort_order' => $this->sort_order,
            'widgets_count' => $this->when(isset($this->widgets_count), $this->widgets_count),
            'shares_count' => $this->when(isset($this->shares_count), $this->shares_count),
            'widgets' => AnalyticsWidgetResource::collection($this->whenLoaded('widgets')),
            'creator' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id' => $this->creator->id,
                    'uuid' => $this->creator->uuid,
                    'full_name' => $this->creator->full_name,
                    'email' => $this->creator->email,
                ] : null;
            }),
            'updater' => $this->whenLoaded('updater', function () {
                return $this->updater ? [
                    'id' => $this->updater->id,
                    'uuid' => $this->updater->uuid,
                    'full_name' => $this->updater->full_name,
                    'email' => $this->updater->email,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

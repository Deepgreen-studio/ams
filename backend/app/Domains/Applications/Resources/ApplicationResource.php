<?php

namespace App\Domains\Applications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'uuid' => $this->company->uuid,
                    'company_name' => $this->company->company_name,
                    'status' => $this->company->status?->value ?? $this->company->status ?? null,
                ];
            }),
            'integration_id' => $this->integration_id,
            'integration' => $this->whenLoaded('integration', function () {
                if (! $this->integration) {
                    return null;
                }

                return [
                    'id' => $this->integration->id,
                    'uuid' => $this->integration->uuid,
                    'name' => $this->integration->name,
                    'slug' => $this->integration->slug,
                    'status' => $this->integration->status?->value ?? $this->integration->status,
                    'type' => $this->integration->type?->value ?? $this->integration->type ?? null,
                ];
            }),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'platform' => $this->platform?->value ?? $this->platform,
            'platform_label' => $this->platform?->label(),
            'category' => $this->category?->value ?? $this->category,
            'category_label' => $this->category?->label(),
            'icon' => $this->icon,
            'banner' => $this->banner,
            'current_version' => $this->current_version,
            'minimum_supported_version' => $this->minimum_supported_version,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'visibility' => $this->visibility?->value ?? $this->visibility,
            'visibility_label' => $this->visibility?->label(),
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

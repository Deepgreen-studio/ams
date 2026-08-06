<?php

namespace App\Domains\Applications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationEnvironmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'application_id' => $this->application_id,
            'application' => $this->whenLoaded('application', function () {
                return [
                    'id' => $this->application->id,
                    'uuid' => $this->application->uuid,
                    'name' => $this->application->name,
                    'slug' => $this->application->slug,
                    'status' => $this->application->status?->value ?? $this->application->status,
                ];
            }),
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type?->value ?? $this->type,
            'type_label' => $this->type?->label(),
            'api_url' => $this->api_url,
            'web_url' => $this->web_url,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'health_status' => $this->health_status?->value ?? $this->health_status,
            'health_status_label' => $this->health_status?->label(),
            'last_health_check' => $this->last_health_check,
            'is_current' => (bool) $this->is_current,
            'has_variables' => $this->has_variables,
            'variable_keys' => $this->variable_keys,
            'variables' => $this->masked_variables,
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

<?php

namespace App\Domains\Integrations\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type?->value ?? $this->type,
            'type_label' => $this->type?->label(),
            'status' => $this->status?->value ?? $this->status,
            'authentication_type' => $this->authentication_type?->value ?? $this->authentication_type,
            'authentication_type_label' => $this->authentication_type?->label(),
            'base_url' => $this->base_url,
            'api_version' => $this->api_version,
            'timeout' => $this->timeout,
            'retry_attempts' => $this->retry_attempts,
            'default_headers' => $this->default_headers ?? [],
            'default_query' => $this->default_query ?? [],
            'rate_limit_per_minute' => $this->rate_limit_per_minute,
            'health_check_path' => $this->health_check_path,
            'has_credentials' => $this->has_credentials,
            'credential_keys' => $this->credential_keys,
            'health_status' => $this->health_status?->value ?? $this->health_status,
            'last_health_check' => $this->last_health_check,
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

<?php

namespace App\Domains\Customers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'customer_id' => $this->customer_id,
            'application_id' => $this->application_id,
            'application_environment_id' => $this->application_environment_id,
            'integration_id' => $this->integration_id,
            'owner_contact_id' => $this->owner_contact_id,
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'uuid' => $this->customer->uuid,
                    'display_name' => $this->customer->display_name,
                    'email' => $this->customer->email,
                    'status' => $this->customer->status?->value ?? $this->customer->status,
                    'company' => $this->customer->relationLoaded('company') && $this->customer->company
                        ? [
                            'id' => $this->customer->company->id,
                            'uuid' => $this->customer->company->uuid,
                            'company_name' => $this->customer->company->company_name,
                        ]
                        : null,
                ];
            }),
            'application' => $this->whenLoaded('application', function () {
                return [
                    'id' => $this->application->id,
                    'uuid' => $this->application->uuid,
                    'name' => $this->application->name,
                    'slug' => $this->application->slug,
                    'platform' => $this->application->platform?->value ?? $this->application->platform,
                    'status' => $this->application->status?->value ?? $this->application->status,
                ];
            }),
            'environment' => $this->whenLoaded('environment', function () {
                return $this->environment ? [
                    'id' => $this->environment->id,
                    'uuid' => $this->environment->uuid,
                    'name' => $this->environment->name,
                    'slug' => $this->environment->slug,
                    'type' => $this->environment->type?->value ?? $this->environment->type,
                    'status' => $this->environment->status?->value ?? $this->environment->status,
                ] : null;
            }),
            'integration' => $this->whenLoaded('integration', function () {
                return $this->integration ? [
                    'id' => $this->integration->id,
                    'uuid' => $this->integration->uuid,
                    'name' => $this->integration->name,
                    'slug' => $this->integration->slug,
                    'type' => $this->integration->type?->value ?? $this->integration->type,
                    'status' => $this->integration->status?->value ?? $this->integration->status,
                ] : null;
            }),
            'owner_contact' => $this->whenLoaded('ownerContact', function () {
                return $this->ownerContact ? [
                    'id' => $this->ownerContact->id,
                    'uuid' => $this->ownerContact->uuid,
                    'name' => $this->ownerContact->name,
                    'email' => $this->ownerContact->email,
                    'contact_type' => $this->ownerContact->contact_type?->value ?? $this->ownerContact->contact_type,
                    'status' => $this->ownerContact->status?->value ?? $this->ownerContact->status,
                ] : null;
            }),
            'ownership_type' => $this->ownership_type?->value ?? $this->ownership_type,
            'status' => $this->status?->value ?? $this->status,
            'activated_at' => $this->activated_at,
            'expires_at' => $this->expires_at,
            'notes' => $this->notes,
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

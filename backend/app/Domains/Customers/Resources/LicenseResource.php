<?php

namespace App\Domains\Customers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'subscription_id' => $this->subscription_id,
            'customer_id' => $this->customer_id,
            'customer_application_id' => $this->customer_application_id,
            'subscription' => $this->whenLoaded('subscription', fn () => $this->subscription ? [
                'id' => $this->subscription->id,
                'uuid' => $this->subscription->uuid,
                'plan_name' => $this->subscription->plan_name,
                'plan_type' => $this->subscription->plan_type?->value ?? $this->subscription->plan_type,
                'status' => $this->subscription->status?->value ?? $this->subscription->status,
                'payment_status' => $this->subscription->payment_status?->value ?? $this->subscription->payment_status,
            ] : null),
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'uuid' => $this->customer->uuid,
                'display_name' => $this->customer->display_name,
                'email' => $this->customer->email,
            ] : null),
            'customer_application' => $this->whenLoaded('customerApplication', function () {
                if (! $this->customerApplication) {
                    return null;
                }

                return [
                    'id' => $this->customerApplication->id,
                    'uuid' => $this->customerApplication->uuid,
                    'application' => $this->customerApplication->relationLoaded('application') && $this->customerApplication->application
                        ? [
                            'uuid' => $this->customerApplication->application->uuid,
                            'name' => $this->customerApplication->application->name,
                        ]
                        : null,
                ];
            }),
            'license_key' => $this->license_key,
            'status' => $this->status?->value ?? $this->status,
            'starts_at' => $this->starts_at,
            'expires_at' => $this->expires_at,
            'features' => $this->features,
            'max_activations' => $this->max_activations,
            'activation_count' => $this->activation_count,
            'last_validated_at' => $this->last_validated_at,
            'revoked_at' => $this->revoked_at,
            'revoked_reason' => $this->revoked_reason,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

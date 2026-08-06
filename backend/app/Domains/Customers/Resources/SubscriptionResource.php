<?php

namespace App\Domains\Customers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'customer_id' => $this->customer_id,
            'customer_application_id' => $this->customer_application_id,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'uuid' => $this->customer->uuid,
                'display_name' => $this->customer->display_name,
                'email' => $this->customer->email,
            ]),
            'customer_application' => $this->whenLoaded('customerApplication', function () {
                if (! $this->customerApplication) {
                    return null;
                }

                return [
                    'id' => $this->customerApplication->id,
                    'uuid' => $this->customerApplication->uuid,
                    'status' => $this->customerApplication->status?->value ?? $this->customerApplication->status,
                    'application' => $this->customerApplication->relationLoaded('application') && $this->customerApplication->application
                        ? [
                            'id' => $this->customerApplication->application->id,
                            'uuid' => $this->customerApplication->application->uuid,
                            'name' => $this->customerApplication->application->name,
                            'slug' => $this->customerApplication->application->slug,
                        ]
                        : null,
                ];
            }),
            'licenses' => LicenseResource::collection($this->whenLoaded('licenses')),
            'licenses_count' => $this->whenCounted('licenses'),
            'plan_type' => $this->plan_type?->value ?? $this->plan_type,
            'plan_name' => $this->plan_name,
            'status' => $this->status?->value ?? $this->status,
            'starts_at' => $this->starts_at,
            'expires_at' => $this->expires_at,
            'renews_at' => $this->renews_at,
            'trial_ends_at' => $this->trial_ends_at,
            'cancelled_at' => $this->cancelled_at,
            'features' => $this->features,
            'payment_status' => $this->payment_status?->value ?? $this->payment_status,
            'payment_provider' => $this->payment_provider?->value ?? $this->payment_provider,
            'external_subscription_id' => $this->external_subscription_id,
            'external_customer_id' => $this->external_customer_id,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'renewal_reminder_days' => $this->renewal_reminder_days,
            'last_renewal_reminder_at' => $this->last_renewal_reminder_at,
            'is_renewal_due_soon' => $this->isRenewalDueSoon(),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

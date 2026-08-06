<?php

namespace App\Domains\Authentication\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticatedUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'name' => $this->full_name ?: $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar_url,
            'status' => $this->status?->value ?? $this->status,
            'is_active' => (bool) $this->is_active,
            'timezone' => $this->timezone,
            'language' => $this->language,
            'email_verified' => $this->hasVerifiedEmail(),
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
            'roles' => $this->getRoleNames()->values(),
            'permissions' => $this->getAllPermissions()->pluck('name')->values(),
            'is_portal_customer' => $this->isPortalCustomer(),
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', function () {
                if (! $this->customer) {
                    return null;
                }

                return [
                    'uuid' => $this->customer->uuid,
                    'display_name' => $this->customer->display_name,
                    'email' => $this->customer->email,
                    'company' => $this->customer->relationLoaded('company') && $this->customer->company
                        ? [
                            'uuid' => $this->customer->company->uuid,
                            'company_name' => $this->customer->company->company_name,
                        ]
                        : null,
                ];
            }),
        ];
    }
}

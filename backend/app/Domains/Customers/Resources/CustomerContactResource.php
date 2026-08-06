<?php

namespace App\Domains\Customers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerContactResource extends JsonResource
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
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'uuid' => $this->customer->uuid,
                    'display_name' => $this->customer->display_name,
                    'email' => $this->customer->email,
                    'customer_type' => $this->customer->customer_type?->value ?? $this->customer->customer_type,
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
            'contact_type' => $this->contact_type?->value ?? $this->contact_type,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'position' => $this->position,
            'department' => $this->department,
            'status' => $this->status?->value ?? $this->status,
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

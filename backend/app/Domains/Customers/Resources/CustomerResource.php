<?php

namespace App\Domains\Customers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'customer_number' => $this->customer_number,
            'reference' => $this->reference,
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'uuid' => $this->company->uuid,
                    'company_name' => $this->company->company_name,
                    'status' => $this->company->status?->value ?? $this->company->status,
                    'country' => $this->company->country,
                    'timezone' => $this->company->timezone,
                ];
            }),
            'customer_type' => $this->customer_type?->value ?? $this->customer_type,
            'customer_type_label' => $this->customer_type?->label(),
            'is_organization' => (bool) $this->customer_type?->isOrganization(),
            'organization_category' => $this->customer_type?->organizationCategory(),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'company_name' => $this->company_name,
            'organization_name' => $this->customer_type?->isOrganization() ? $this->company_name : null,
            'legal_name' => $this->legal_name,
            'registration_number' => $this->registration_number,
            'display_name' => $this->display_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'primary_contact_name' => $this->primary_contact_name,
            'primary_contact_email' => $this->primary_contact_email,
            'primary_contact_phone' => $this->primary_contact_phone,
            'primary_contact_title' => $this->primary_contact_title,
            'website' => $this->website,
            'industry' => $this->industry,
            'industry_other' => $this->industry_other,
            'industry_master' => $this->whenLoaded('industryMaster', function () {
                return $this->industryMaster ? [
                    'uuid' => $this->industryMaster->uuid,
                    'code' => $this->industryMaster->code,
                    'name' => $this->industryMaster->name,
                    'is_other' => (bool) $this->industryMaster->is_other,
                ] : null;
            }),
            'sub_industry' => $this->whenLoaded('subIndustry', function () {
                return $this->subIndustry ? [
                    'uuid' => $this->subIndustry->uuid,
                    'code' => $this->subIndustry->code,
                    'name' => $this->subIndustry->name,
                ] : null;
            }),
            'country' => $this->country,
            'timezone' => $this->timezone,
            'language' => $this->language,
            'legal_basis' => $this->legal_basis?->value ?? $this->legal_basis,
            'legal_basis_label' => $this->legal_basis?->label(),
            'processing_purpose' => $this->processing_purpose,
            'retention_until' => $this->retention_until?->toDateString(),
            'anonymized_at' => $this->anonymized_at,
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

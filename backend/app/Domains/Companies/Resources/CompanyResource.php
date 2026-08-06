<?php

namespace App\Domains\Companies\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_name' => $this->company_name,
            'legal_name' => $this->legal_name,
            'registration_number' => $this->registration_number,
            'tax_number' => $this->tax_number,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'logo' => $this->logo,
            'logo_url' => $this->logo_url,
            'favicon' => $this->favicon,
            'favicon_url' => $this->favicon_url,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'timezone' => $this->timezone,
            'language' => $this->language,
            'currency' => $this->currency,
            'date_format' => $this->date_format,
            'time_format' => $this->time_format,
            'business_hours' => $this->business_hours,
            'settings' => $this->settings,
            'status' => $this->status?->value ?? $this->status,
            'departments_count' => $this->whenCounted('departments'),
            'teams_count' => $this->whenCounted('teams'),
            'locations_count' => $this->whenCounted('locations'),
            'departments' => DepartmentResource::collection($this->whenLoaded('departments')),
            'teams' => TeamResource::collection($this->whenLoaded('teams')),
            'locations' => LocationResource::collection($this->whenLoaded('locations')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

<?php

namespace App\Domains\Companies\Resources;

use App\Shared\Support\CountryCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $legal = $this->canViewLegal($request);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_name' => $this->company_name,
            'company_code' => $this->company_code,
            'legal_name' => $this->when($legal, $this->legal_name),
            'registration_number' => $this->when($legal, $this->registration_number),
            'tax_number' => $this->when($legal, $this->tax_number),
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
            'country_name' => CountryCatalog::name($this->country),
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
            'creator' => $this->whenLoaded('creator', fn () => [
                'uuid' => $this->creator?->uuid,
                'full_name' => $this->creator?->full_name,
            ]),
            'updater' => $this->whenLoaded('updater', fn () => [
                'uuid' => $this->updater->uuid,
                'full_name' => $this->updater->full_name,
            ]),
        ];
    }

    private function canViewLegal(Request $request): bool
    {
        $user = $request->user();

        return $user !== null && (
            $user->hasRole('super-admin')
            || $user->can('companies.update')
            || $user->can('companies.manage')
        );
    }
}

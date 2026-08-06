<?php

namespace App\Domains\Support\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportSlaCalendarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'timezone' => $this->timezone,
            'business_hours' => $this->business_hours,
            'is_default' => (bool) $this->is_default,
            'is_active' => (bool) $this->is_active,
            'company' => $this->whenLoaded('company', fn () => $this->company ? [
                'uuid' => $this->company->uuid,
                'company_name' => $this->company->company_name,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

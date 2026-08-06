<?php

namespace App\Domains\Support\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportSlaHolidayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'holiday_date' => $this->holiday_date?->toDateString(),
            'is_recurring' => (bool) $this->is_recurring,
            'is_active' => (bool) $this->is_active,
            'company' => $this->whenLoaded('company', fn () => $this->company ? [
                'uuid' => $this->company->uuid,
                'company_name' => $this->company->company_name,
            ] : null),
            'calendar' => $this->whenLoaded('calendar', fn () => $this->calendar ? [
                'uuid' => $this->calendar->uuid,
                'name' => $this->calendar->name,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

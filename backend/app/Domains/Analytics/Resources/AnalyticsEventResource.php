<?php

namespace App\Domains\Analytics\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', function () {
                return $this->company ? [
                    'id' => $this->company->id,
                    'uuid' => $this->company->uuid,
                    'company_name' => $this->company->company_name,
                ] : null;
            }),
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'uuid' => $this->user->uuid,
                    'full_name' => $this->user->full_name,
                    'email' => $this->user->email,
                ] : null;
            }),
            'application' => $this->whenLoaded('application', function () {
                return $this->application ? [
                    'id' => $this->application->id,
                    'uuid' => $this->application->uuid,
                    'name' => $this->application->name,
                ] : null;
            }),
            'customer_id' => $this->customer_id,
            'category' => $this->category?->value ?? $this->category,
            'event_name' => $this->event_name,
            'event_source' => $this->event_source,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'properties' => $this->properties,
            'metrics' => $this->metrics,
            'ip_address' => $this->ip_address,
            'occurred_at' => $this->occurred_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

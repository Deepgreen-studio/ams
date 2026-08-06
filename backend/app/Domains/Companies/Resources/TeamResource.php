<?php

namespace App\Domains\Companies\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company_id,
            'department_id' => $this->department_id,
            'manager_id' => $this->manager_id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status?->value ?? $this->status,
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'uuid' => $this->company->uuid,
                'company_name' => $this->company->company_name,
            ]),
            'department' => $this->whenLoaded('department', fn () => $this->department ? [
                'id' => $this->department->id,
                'uuid' => $this->department->uuid,
                'name' => $this->department->name,
            ] : null),
            'manager' => $this->whenLoaded('manager', fn () => $this->manager ? [
                'id' => $this->manager->id,
                'uuid' => $this->manager->uuid,
                'full_name' => $this->manager->full_name,
                'email' => $this->manager->email,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Domains\Companies\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'department_name' => $this->name,
            'description' => $this->description,
            'note' => $this->note,
            'status' => $this->status?->value ?? $this->status,
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'uuid' => $this->company->uuid,
                'company_name' => $this->company->company_name,
            ]),
            'teams' => $this->whenLoaded('teams', fn () => $this->teams->map(fn ($team) => [
                'id' => $team->id,
                'uuid' => $team->uuid,
                'name' => $team->name,
                'description' => $team->description,
                'status' => $team->status?->value ?? $team->status,
            ])->values()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Domains\Roles\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'module' => $this->module,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'permissions' => $this->whenLoaded('permissions', function () {
                return PermissionResource::collection($this->permissions);
            }),
        ];
    }
}

<?php

namespace App\Domains\Roles\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'module' => $this->module,
            'description' => $this->description,
            'guard_name' => $this->guard_name,
            'group' => $this->whenLoaded('group', function () {
                return $this->group ? [
                    'id' => $this->group->id,
                    'uuid' => $this->group->uuid,
                    'name' => $this->group->name,
                    'slug' => $this->group->slug,
                    'module' => $this->group->module,
                ] : null;
            }),
        ];
    }
}

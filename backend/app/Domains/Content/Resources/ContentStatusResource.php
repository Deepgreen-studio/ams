<?php

namespace App\Domains\Content\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'color' => $this->color,
            'is_system' => (bool) $this->is_system,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}

<?php

namespace App\Domains\Audit\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'event' => $this->event,
            'module' => $this->module,
            'level' => $this->level,
            'payload' => $this->payload,
            'created_at' => $this->created_at,
        ];
    }
}

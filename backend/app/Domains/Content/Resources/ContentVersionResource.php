<?php

namespace App\Domains\Content\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentVersionResource extends JsonResource
{
    public bool $withSnapshot = false;

    public function withSnapshot(bool $include = true): static
    {
        $this->withSnapshot = $include;

        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'version' => (int) $this->version,
            'status' => $this->status,
            'snapshot' => $this->when($this->withSnapshot, $this->snapshot),
            'reason' => $this->reason,
            'creator' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id' => $this->creator->id,
                    'uuid' => $this->creator->uuid,
                    'full_name' => $this->creator->full_name,
                    'email' => $this->creator->email,
                ] : null;
            }),
            'created_at' => $this->created_at,
        ];
    }
}

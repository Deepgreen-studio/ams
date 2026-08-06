<?php

namespace App\Domains\Compliance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PolicyVersionResource extends JsonResource
{
    protected bool $withSnapshot = false;

    public function withSnapshot(bool $value = true): static
    {
        $this->withSnapshot = $value;

        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'policy_id' => $this->policy_id,
            'version' => $this->version,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'title' => $this->title,
            'body' => $this->when($this->withSnapshot, $this->body),
            'snapshot' => $this->when($this->withSnapshot, $this->snapshot),
            'reason' => $this->reason,
            'is_restore' => $this->is_restore,
            'restored_from_version' => $this->restored_from_version,
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

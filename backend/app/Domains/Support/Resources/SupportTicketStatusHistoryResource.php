<?php

namespace App\Domains\Support\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'from_status' => $this->from_status,
            'from_status_label' => $this->labelFor($this->from_status),
            'to_status' => $this->to_status,
            'to_status_label' => $this->labelFor($this->to_status),
            'action' => $this->action?->value ?? $this->action,
            'action_label' => $this->action?->label(),
            'comments' => $this->comments,
            'metadata' => $this->metadata,
            'actor' => $this->whenLoaded('actor', fn () => $this->actor ? [
                'uuid' => $this->actor->uuid,
                'full_name' => $this->actor->full_name,
                'email' => $this->actor->email,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }

    protected function labelFor(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        return \App\Domains\Support\Enums\SupportTicketStatus::tryFrom($status)?->label() ?? $status;
    }
}

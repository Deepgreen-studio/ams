<?php

namespace App\Domains\Content\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentWorkflowHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'action' => $this->action,
            'approval_level' => $this->approval_level,
            'comments' => $this->comments,
            'metadata' => $this->metadata,
            'actor' => $this->whenLoaded('actor', function () {
                return $this->actor ? [
                    'id' => $this->actor->id,
                    'uuid' => $this->actor->uuid,
                    'full_name' => $this->actor->full_name,
                    'email' => $this->actor->email,
                ] : null;
            }),
            'created_at' => $this->created_at,
        ];
    }
}

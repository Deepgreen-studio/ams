<?php

namespace App\Domains\Monitoring\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company_id,
            'level' => $this->level?->value ?? $this->level,
            'category' => $this->category?->value ?? $this->category,
            'source' => $this->source,
            'title' => $this->title,
            'message' => $this->message,
            'context' => $this->context,
            'occurred_at' => $this->occurred_at,
            'created_at' => $this->created_at,
        ];
    }
}

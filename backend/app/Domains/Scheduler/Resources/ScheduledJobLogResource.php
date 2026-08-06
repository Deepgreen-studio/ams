<?php

namespace App\Domains\Scheduler\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduledJobLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'level' => $this->level,
            'message' => $this->message,
            'context' => $this->context,
            'run' => $this->whenLoaded('run', fn () => [
                'uuid' => $this->run?->uuid,
                'status' => $this->run?->status?->value ?? $this->run?->status,
                'job' => $this->run?->relationLoaded('job') ? [
                    'uuid' => $this->run?->job?->uuid,
                    'name' => $this->run?->job?->name,
                    'handler_key' => $this->run?->job?->handler_key,
                ] : null,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}

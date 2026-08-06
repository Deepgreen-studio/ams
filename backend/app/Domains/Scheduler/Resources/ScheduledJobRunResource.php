<?php

namespace App\Domains\Scheduler\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduledJobRunResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'trigger' => $this->trigger,
            'attempt' => (int) $this->attempt,
            'queue_name' => $this->queue_name,
            'queue_job_id' => $this->queue_job_id,
            'payload' => $this->payload,
            'result' => $this->result,
            'error_message' => $this->error_message,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'duration_ms' => $this->duration_ms,
            'job' => $this->whenLoaded('job', fn () => [
                'uuid' => $this->job?->uuid,
                'name' => $this->job?->name,
                'handler_key' => $this->job?->handler_key,
                'job_type' => $this->job?->job_type?->value ?? $this->job?->job_type,
            ]),
            'triggerer' => $this->whenLoaded('triggerer', fn () => [
                'uuid' => $this->triggerer?->uuid,
                'full_name' => $this->triggerer?->full_name,
            ]),
            'logs' => ScheduledJobLogResource::collection($this->whenLoaded('logs')),
            'created_at' => $this->created_at,
        ];
    }
}

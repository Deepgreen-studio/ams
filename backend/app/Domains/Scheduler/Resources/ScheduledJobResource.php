<?php

namespace App\Domains\Scheduler\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduledJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company?->uuid,
            'company' => $this->whenLoaded('company', fn () => [
                'uuid' => $this->company?->uuid,
                'company_name' => $this->company?->company_name,
            ]),
            'name' => $this->name,
            'description' => $this->description,
            'job_type' => $this->job_type?->value ?? $this->job_type,
            'job_type_label' => $this->job_type?->label(),
            'handler_key' => $this->handler_key,
            'schedule_cron' => $this->schedule_cron,
            'timezone' => $this->timezone,
            'run_at' => $this->run_at,
            'delay_minutes' => $this->delay_minutes,
            'queue_name' => $this->queue_name,
            'is_enabled' => (bool) $this->is_enabled,
            'without_overlapping' => (bool) $this->without_overlapping,
            'max_attempts' => (int) $this->max_attempts,
            'timeout_seconds' => $this->timeout_seconds,
            'payload' => $this->payload,
            'metadata' => $this->metadata,
            'last_run_at' => $this->last_run_at,
            'next_run_at' => $this->next_run_at,
            'last_status' => $this->last_status,
            'creator' => $this->whenLoaded('creator', fn () => [
                'uuid' => $this->creator?->uuid,
                'full_name' => $this->creator?->full_name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Domains\Queue\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueueJobTrackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'job_uuid' => $this->job_uuid,
            'job_class' => $this->job_class,
            'display_name' => $this->display_name,
            'queue' => $this->queue,
            'priority' => $this->priority,
            'type' => $this->type?->value ?? $this->type,
            'status' => $this->status?->value ?? $this->status,
            'attempts' => $this->attempts,
            'max_tries' => $this->max_tries,
            'delay_seconds' => $this->delay_seconds,
            'payload' => $this->payload ?? [],
            'exception' => $this->exception,
            'queued_at' => $this->queued_at,
            'available_at' => $this->available_at,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'failed_at' => $this->failed_at,
            'company' => $this->whenLoaded('company', fn () => $this->company ? [
                'uuid' => $this->company->uuid,
                'company_name' => $this->company->company_name,
            ] : null),
            'actor' => $this->whenLoaded('actor', fn () => $this->actor ? [
                'uuid' => $this->actor->uuid,
                'full_name' => $this->actor->full_name,
                'email' => $this->actor->email,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

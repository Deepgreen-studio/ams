<?php

namespace App\Domains\Integrations\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SyncRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'config' => $this->whenLoaded('config', fn () => $this->config ? [
                'id' => $this->config->id,
                'uuid' => $this->config->uuid,
                'name' => $this->config->name,
                'slug' => $this->config->slug,
            ] : null),
            'integration' => $this->whenLoaded('integration', fn () => $this->integration ? [
                'id' => $this->integration->id,
                'uuid' => $this->integration->uuid,
                'name' => $this->integration->name,
            ] : null),
            'trigger' => $this->trigger?->value ?? $this->trigger,
            'mode' => $this->mode?->value ?? $this->mode,
            'direction' => $this->direction?->value ?? $this->direction,
            'status' => $this->status?->value ?? $this->status,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'failed_at' => $this->failed_at,
            'total_records' => $this->total_records,
            'imported' => $this->imported,
            'exported' => $this->exported,
            'updated' => $this->updated,
            'failed' => $this->failed,
            'skipped' => $this->skipped,
            'progress_percent' => $this->progress_percent,
            'error_message' => $this->error_message,
            'meta' => $this->meta,
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

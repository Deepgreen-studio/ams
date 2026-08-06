<?php

namespace App\Domains\Analytics\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsReportRunResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'analytics_report_id' => $this->analytics_report_id,
            'status' => $this->status?->value ?? $this->status,
            'format' => $this->format?->value ?? $this->format,
            'trigger' => $this->trigger,
            'filters_snapshot' => $this->filters_snapshot,
            'result_meta' => $this->result_meta,
            'row_count' => $this->row_count,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'error_message' => $this->error_message,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'creator' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id' => $this->creator->id,
                    'uuid' => $this->creator->uuid,
                    'full_name' => $this->creator->full_name,
                    'email' => $this->creator->email,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

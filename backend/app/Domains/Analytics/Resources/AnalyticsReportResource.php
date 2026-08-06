<?php

namespace App\Domains\Analytics\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsReportResource extends JsonResource
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
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category?->value ?? $this->category,
            'report_type' => $this->report_type?->value ?? $this->report_type,
            'status' => $this->status?->value ?? $this->status,
            'visibility' => $this->visibility?->value ?? $this->visibility,
            'is_saved' => (bool) $this->is_saved,
            'is_scheduled' => (bool) $this->is_scheduled,
            'query_config' => $this->query_config,
            'columns' => $this->columns,
            'filters' => $this->filters,
            'sorting' => $this->sorting,
            'grouping' => $this->grouping,
            'chart_config' => $this->chart_config,
            'layout' => $this->layout,
            'schedule_config' => $this->schedule_config,
            'scheduled_job_id' => $this->scheduled_job_id,
            'format_defaults' => $this->format_defaults,
            'last_run_at' => $this->last_run_at,
            'runs_count' => $this->when(isset($this->runs_count), $this->runs_count),
            'owner' => $this->whenLoaded('owner', function () {
                return $this->owner ? [
                    'id' => $this->owner->id,
                    'uuid' => $this->owner->uuid,
                    'full_name' => $this->owner->full_name,
                    'email' => $this->owner->email,
                ] : null;
            }),
            'scheduled_job' => $this->whenLoaded('scheduledJob', function () {
                return $this->scheduledJob ? [
                    'id' => $this->scheduledJob->id,
                    'uuid' => $this->scheduledJob->uuid,
                    'name' => $this->scheduledJob->name,
                    'schedule_cron' => $this->scheduledJob->schedule_cron,
                    'is_enabled' => (bool) $this->scheduledJob->is_enabled,
                    'next_run_at' => $this->scheduledJob->next_run_at,
                    'payload' => $this->scheduledJob->payload,
                ] : null;
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id' => $this->creator->id,
                    'uuid' => $this->creator->uuid,
                    'full_name' => $this->creator->full_name,
                    'email' => $this->creator->email,
                ] : null;
            }),
            'updater' => $this->whenLoaded('updater', function () {
                return $this->updater ? [
                    'id' => $this->updater->id,
                    'uuid' => $this->updater->uuid,
                    'full_name' => $this->updater->full_name,
                    'email' => $this->updater->email,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

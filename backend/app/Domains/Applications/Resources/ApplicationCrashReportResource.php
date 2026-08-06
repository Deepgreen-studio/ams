<?php

namespace App\Domains\Applications\Resources;

use App\Domains\Applications\Enums\ApplicationCrashSeverity;
use App\Domains\Applications\Enums\ApplicationCrashStatus;
use App\Domains\Applications\Enums\ApplicationCrashType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationCrashReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = $this->type instanceof ApplicationCrashType ? $this->type : ApplicationCrashType::tryFrom((string) $this->type);
        $severity = $this->severity instanceof ApplicationCrashSeverity ? $this->severity : ApplicationCrashSeverity::tryFrom((string) $this->severity);
        $status = $this->status instanceof ApplicationCrashStatus ? $this->status : ApplicationCrashStatus::tryFrom((string) $this->status);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'application_id' => $this->application_id,
            'application_version_id' => $this->application_version_id,
            'version_label' => $this->version_label,
            'type' => $type?->value ?? $this->type,
            'type_label' => $type?->label(),
            'severity' => $severity?->value ?? $this->severity,
            'severity_label' => $severity?->label(),
            'status' => $status?->value ?? $this->status,
            'status_label' => $status?->label(),
            'title' => $this->title,
            'message' => $this->message,
            'stack_trace' => $this->stack_trace,
            'crash_log' => $this->crash_log,
            'fingerprint' => $this->fingerprint,
            'occurrence_count' => $this->occurrence_count,
            'device_id' => $this->device_id,
            'device_model' => $this->device_model,
            'device_manufacturer' => $this->device_manufacturer,
            'device_os' => $this->device_os,
            'device_os_version' => $this->device_os_version,
            'device_meta' => $this->device_meta,
            'endpoint' => $this->endpoint,
            'http_status' => $this->http_status,
            'response_time_ms' => $this->response_time_ms,
            'memory_usage_mb' => $this->memory_usage_mb,
            'battery_level' => $this->battery_level,
            'occurred_at' => $this->occurred_at,
            'resolved_at' => $this->resolved_at,
            'metadata' => $this->metadata,
            'version' => $this->whenLoaded('version', function () {
                return $this->version ? [
                    'id' => $this->version->id,
                    'uuid' => $this->version->uuid,
                    'version_number' => $this->version->version_number,
                    'status' => $this->version->status?->value ?? $this->version->status,
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

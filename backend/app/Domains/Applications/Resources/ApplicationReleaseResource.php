<?php

namespace App\Domains\Applications\Resources;

use App\Domains\Applications\Enums\ApplicationReleaseApprovalStatus;
use App\Domains\Applications\Enums\ApplicationReleaseRollbackStatus;
use App\Domains\Applications\Enums\ApplicationReleaseStatus;
use App\Domains\Applications\Enums\ApplicationReleaseType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationReleaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = $this->release_type instanceof ApplicationReleaseType
            ? $this->release_type
            : ApplicationReleaseType::tryFrom((string) $this->release_type);
        $status = $this->status instanceof ApplicationReleaseStatus
            ? $this->status
            : ApplicationReleaseStatus::tryFrom((string) $this->status);
        $approval = $this->approval_status instanceof ApplicationReleaseApprovalStatus
            ? $this->approval_status
            : ApplicationReleaseApprovalStatus::tryFrom((string) $this->approval_status);
        $rollback = $this->rollback_status instanceof ApplicationReleaseRollbackStatus
            ? $this->rollback_status
            : ApplicationReleaseRollbackStatus::tryFrom((string) $this->rollback_status);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'application_id' => $this->application_id,
            'application_version_id' => $this->application_version_id,
            'environment_id' => $this->environment_id,
            'name' => $this->name,
            'version_label' => $this->version_label,
            'release_type' => $type?->value ?? $this->release_type,
            'release_type_label' => $type?->label(),
            'status' => $status?->value ?? $this->status,
            'status_label' => $status?->label(),
            'approval_status' => $approval?->value ?? $this->approval_status,
            'approval_status_label' => $approval?->label(),
            'rollback_status' => $rollback?->value ?? $this->rollback_status,
            'rollback_status_label' => $rollback?->label(),
            'scheduled_at' => $this->scheduled_at,
            'deployment_date' => $this->deployment_date,
            'deployed_at' => $this->deployed_at,
            'approved_at' => $this->approved_at,
            'approval_notes' => $this->approval_notes,
            'rolled_back_at' => $this->rolled_back_at,
            'plan_summary' => $this->plan_summary,
            'metadata' => $this->metadata,
            'version' => $this->whenLoaded('version', function () {
                return $this->version ? [
                    'id' => $this->version->id,
                    'uuid' => $this->version->uuid,
                    'version_number' => $this->version->version_number,
                    'status' => $this->version->status?->value ?? $this->version->status,
                    'build_number' => $this->version->build_number,
                ] : null;
            }),
            'environment' => $this->whenLoaded('environment', function () {
                return $this->environment ? [
                    'id' => $this->environment->id,
                    'uuid' => $this->environment->uuid,
                    'name' => $this->environment->name,
                    'slug' => $this->environment->slug,
                    'type' => $this->environment->type?->value ?? $this->environment->type,
                ] : null;
            }),
            'notes' => ApplicationReleaseNoteResource::collection($this->whenLoaded('notes')),
            'approver' => $this->whenLoaded('approver', function () {
                return $this->approver ? [
                    'id' => $this->approver->id,
                    'uuid' => $this->approver->uuid,
                    'full_name' => $this->approver->full_name,
                    'email' => $this->approver->email,
                ] : null;
            }),
            'rolled_back_by' => $this->whenLoaded('rolledBackBy', function () {
                return $this->rolledBackBy ? [
                    'id' => $this->rolledBackBy->id,
                    'uuid' => $this->rolledBackBy->uuid,
                    'full_name' => $this->rolledBackBy->full_name,
                    'email' => $this->rolledBackBy->email,
                ] : null;
            }),
            'rollback_of' => $this->whenLoaded('rollbackOf', function () {
                return $this->rollbackOf ? [
                    'id' => $this->rollbackOf->id,
                    'uuid' => $this->rollbackOf->uuid,
                    'name' => $this->rollbackOf->name,
                    'version_label' => $this->rollbackOf->version_label,
                    'status' => $this->rollbackOf->status?->value ?? $this->rollbackOf->status,
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

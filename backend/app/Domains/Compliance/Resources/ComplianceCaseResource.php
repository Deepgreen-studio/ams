<?php

namespace App\Domains\Compliance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplianceCaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'uuid' => $this->company->uuid,
                    'company_name' => $this->company->company_name,
                    'status' => $this->company->status?->value ?? $this->company->status ?? null,
                ];
            }),
            'case_number' => $this->case_number,
            'title' => $this->title,
            'description' => $this->description,
            'case_type' => $this->case_type?->value ?? $this->case_type,
            'case_type_label' => $this->case_type?->label(),
            'priority' => $this->priority?->value ?? $this->priority,
            'priority_label' => $this->priority?->label(),
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'assigned_to' => $this->assigned_to,
            'assignee' => $this->whenLoaded('assignee', function () {
                return $this->assignee ? [
                    'id' => $this->assignee->id,
                    'uuid' => $this->assignee->uuid,
                    'full_name' => $this->assignee->full_name,
                    'email' => $this->assignee->email,
                ] : null;
            }),
            'due_date' => optional($this->due_date)?->toDateString(),
            'completed_at' => $this->completed_at,
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

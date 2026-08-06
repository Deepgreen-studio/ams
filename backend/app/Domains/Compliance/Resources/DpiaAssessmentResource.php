<?php

namespace App\Domains\Compliance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DpiaAssessmentResource extends JsonResource
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
                ];
            }),
            'assessment_number' => $this->assessment_number,
            'title' => $this->title,
            'description' => $this->description,
            'template_code' => $this->template_code?->value ?? $this->template_code,
            'template_label' => $this->template_code?->label(),
            'template_defaults' => $this->template_code?->wizardDefaults(),
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'wizard_step' => $this->wizard_step,
            'wizard_payload' => $this->wizard_payload,
            'processing_purpose' => $this->processing_purpose,
            'data_categories' => $this->data_categories,
            'data_subjects' => $this->data_subjects,
            'processing_operations' => $this->processing_operations,
            'necessity_proportionality' => $this->necessity_proportionality,
            'consultation_notes' => $this->consultation_notes,
            'overall_risk_score' => $this->overall_risk_score,
            'overall_risk_level' => $this->overall_risk_level?->value ?? $this->overall_risk_level,
            'overall_risk_level_label' => $this->overall_risk_level?->label(),
            'residual_risk_score' => $this->residual_risk_score,
            'residual_risk_level' => $this->residual_risk_level?->value ?? $this->residual_risk_level,
            'residual_risk_level_label' => $this->residual_risk_level?->label(),
            'mitigation_summary' => $this->mitigation_summary,
            'review_due_at' => optional($this->review_due_at)?->toDateString(),
            'next_review_at' => optional($this->next_review_at)?->toDateString(),
            'reviewed_at' => $this->reviewed_at,
            'submitted_at' => $this->submitted_at,
            'approved_at' => $this->approved_at,
            'approval_notes' => $this->approval_notes,
            'rejected_at' => $this->rejected_at,
            'rejection_notes' => $this->rejection_notes,
            'allowed_transitions' => array_map(
                fn ($status) => $status->value,
                $this->status?->allowedTransitions() ?? []
            ),
            'assignee' => $this->whenLoaded('assignee', fn () => $this->userBrief($this->assignee)),
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->userBrief($this->reviewer)),
            'submitter' => $this->whenLoaded('submitter', fn () => $this->userBrief($this->submitter)),
            'approver' => $this->whenLoaded('approver', fn () => $this->userBrief($this->approver)),
            'rejector' => $this->whenLoaded('rejector', fn () => $this->userBrief($this->rejector)),
            'creator' => $this->whenLoaded('creator', fn () => $this->userBrief($this->creator)),
            'updater' => $this->whenLoaded('updater', fn () => $this->userBrief($this->updater)),
            'risks' => RiskRegisterResource::collection($this->whenLoaded('risks')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }

    private function userBrief(mixed $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'full_name' => $user->full_name,
            'email' => $user->email,
        ];
    }
}

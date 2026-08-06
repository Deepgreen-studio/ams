<?php

namespace App\Domains\Compliance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskRegisterResource extends JsonResource
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
            'dpia_assessment_id' => $this->dpia_assessment_id,
            'dpia_assessment' => $this->whenLoaded('dpiaAssessment', function () {
                if (! $this->dpiaAssessment) {
                    return null;
                }

                return [
                    'id' => $this->dpiaAssessment->id,
                    'uuid' => $this->dpiaAssessment->uuid,
                    'assessment_number' => $this->dpiaAssessment->assessment_number,
                    'title' => $this->dpiaAssessment->title,
                    'status' => $this->dpiaAssessment->status?->value ?? $this->dpiaAssessment->status,
                ];
            }),
            'risk_number' => $this->risk_number,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category?->value ?? $this->category,
            'category_label' => $this->category?->label(),
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'likelihood' => $this->likelihood,
            'impact' => $this->impact,
            'risk_score' => $this->risk_score,
            'risk_level' => $this->risk_level?->value ?? $this->risk_level,
            'risk_level_label' => $this->risk_level?->label(),
            'residual_likelihood' => $this->residual_likelihood,
            'residual_impact' => $this->residual_impact,
            'residual_score' => $this->residual_score,
            'residual_level' => $this->residual_level?->value ?? $this->residual_level,
            'residual_level_label' => $this->residual_level?->label(),
            'mitigation_plan' => $this->mitigation_plan,
            'review_due_at' => optional($this->review_due_at)?->toDateString(),
            'identified_at' => $this->identified_at,
            'closed_at' => $this->closed_at,
            'allowed_transitions' => array_map(
                fn ($status) => $status->value,
                $this->status?->allowedTransitions() ?? []
            ),
            'owner' => $this->whenLoaded('owner', function () {
                return $this->owner ? [
                    'id' => $this->owner->id,
                    'uuid' => $this->owner->uuid,
                    'full_name' => $this->owner->full_name,
                    'email' => $this->owner->email,
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
            'actions' => RiskActionResource::collection($this->whenLoaded('actions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

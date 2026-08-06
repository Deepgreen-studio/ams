<?php

namespace App\Domains\Compliance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataBreachResource extends JsonResource
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
            'breach_number' => $this->breach_number,
            'title' => $this->title,
            'description' => $this->description,
            'breach_type' => $this->breach_type?->value ?? $this->breach_type,
            'breach_type_label' => $this->breach_type?->label(),
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'severity' => $this->severity?->value ?? $this->severity,
            'severity_label' => $this->severity?->label(),
            'discovered_at' => $this->discovered_at,
            'occurred_at' => $this->occurred_at,
            'affected_user_count' => $this->affected_user_count,
            'affected_users' => $this->affected_users,
            'affected_data_categories' => $this->affected_data_categories,
            'personal_data_involved' => $this->personal_data_involved,
            'special_category_data' => $this->special_category_data,
            'risk_likelihood' => $this->risk_likelihood,
            'risk_impact' => $this->risk_impact,
            'risk_score' => $this->risk_score,
            'risk_level' => $this->risk_level?->value ?? $this->risk_level,
            'risk_level_label' => $this->risk_level?->label(),
            'risk_assessment_notes' => $this->risk_assessment_notes,
            'risk_assessed_at' => $this->risk_assessed_at,
            'risk_assessor' => $this->whenLoaded('riskAssessor', function () {
                return $this->riskAssessor ? [
                    'id' => $this->riskAssessor->id,
                    'uuid' => $this->riskAssessor->uuid,
                    'full_name' => $this->riskAssessor->full_name,
                    'email' => $this->riskAssessor->email,
                ] : null;
            }),
            'impact_analysis' => $this->impact_analysis,
            'containment_summary' => $this->containment_summary,
            'contained_at' => $this->contained_at,
            'recovery_summary' => $this->recovery_summary,
            'recovered_at' => $this->recovered_at,
            'root_cause' => $this->root_cause,
            'root_cause_at' => $this->root_cause_at,
            'lessons_learned' => $this->lessons_learned,
            'lessons_learned_at' => $this->lessons_learned_at,
            'regulator_notification_required' => $this->regulator_notification_required,
            'regulator_deadline_at' => $this->regulator_deadline_at,
            'regulator_notified_at' => $this->regulator_notified_at,
            'regulator_reference' => $this->regulator_reference,
            'customer_notification_required' => $this->customer_notification_required,
            'customer_notified_at' => $this->customer_notified_at,
            'assigned_to' => $this->assigned_to,
            'assignee' => $this->whenLoaded('assignee', function () {
                return $this->assignee ? [
                    'id' => $this->assignee->id,
                    'uuid' => $this->assignee->uuid,
                    'full_name' => $this->assignee->full_name,
                    'email' => $this->assignee->email,
                ] : null;
            }),
            'closed_at' => $this->closed_at,
            'allowed_transitions' => array_map(
                fn ($status) => $status->value,
                $this->status?->allowedTransitions() ?? []
            ),
            'actions' => BreachActionResource::collection($this->whenLoaded('actions')),
            'notifications' => BreachNotificationResource::collection($this->whenLoaded('notifications')),
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

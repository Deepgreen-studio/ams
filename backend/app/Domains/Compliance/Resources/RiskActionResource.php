<?php

namespace App\Domains\Compliance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'risk_register_id' => $this->risk_register_id,
            'risk_register' => $this->whenLoaded('riskRegister', function () {
                if (! $this->riskRegister) {
                    return null;
                }

                return [
                    'uuid' => $this->riskRegister->uuid,
                    'risk_number' => $this->riskRegister->risk_number,
                    'title' => $this->riskRegister->title,
                    'status' => $this->riskRegister->status?->value ?? $this->riskRegister->status,
                    'risk_level' => $this->riskRegister->risk_level?->value ?? $this->riskRegister->risk_level,
                    'company' => $this->riskRegister->relationLoaded('company') && $this->riskRegister->company
                        ? [
                            'uuid' => $this->riskRegister->company->uuid,
                            'company_name' => $this->riskRegister->company->company_name,
                        ]
                        : null,
                ];
            }),
            'action_type' => $this->action_type?->value ?? $this->action_type,
            'action_type_label' => $this->action_type?->label(),
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'due_at' => $this->due_at,
            'completed_at' => $this->completed_at,
            'performer' => $this->whenLoaded('performer', function () {
                return $this->performer ? [
                    'id' => $this->performer->id,
                    'uuid' => $this->performer->uuid,
                    'full_name' => $this->performer->full_name,
                    'email' => $this->performer->email,
                ] : null;
            }),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

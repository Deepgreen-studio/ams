<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Compliance\Enums\RiskLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveDpiaWizardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wizard_step' => ['required', 'integer', 'min:1', 'max:10'],
            'wizard_payload' => ['nullable', 'array'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'processing_purpose' => ['nullable', 'string', 'max:20000'],
            'data_categories' => ['nullable', 'array'],
            'data_subjects' => ['nullable', 'array'],
            'processing_operations' => ['nullable', 'string', 'max:20000'],
            'necessity_proportionality' => ['nullable', 'string', 'max:20000'],
            'consultation_notes' => ['nullable', 'string', 'max:20000'],
            'mitigation_summary' => ['nullable', 'string', 'max:20000'],
            'overall_risk_score' => ['nullable', 'integer', 'min:1', 'max:25'],
            'overall_risk_level' => ['nullable', Rule::in(RiskLevel::values())],
            'residual_risk_score' => ['nullable', 'integer', 'min:1', 'max:25'],
            'residual_risk_level' => ['nullable', Rule::in(RiskLevel::values())],
            'review_due_at' => ['nullable', 'date'],
            'next_review_at' => ['nullable', 'date'],
        ];
    }
}

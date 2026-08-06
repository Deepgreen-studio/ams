<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Compliance\Enums\DpiaStatus;
use App\Domains\Compliance\Enums\DpiaTemplate;
use App\Domains\Compliance\Enums\RiskLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDpiaAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'template_code' => ['nullable', Rule::in(DpiaTemplate::values())],
            'status' => ['nullable', Rule::in(DpiaStatus::values())],
            'wizard_step' => ['nullable', 'integer', 'min:1', 'max:10'],
            'wizard_payload' => ['nullable', 'array'],
            'processing_purpose' => ['nullable', 'string', 'max:20000'],
            'data_categories' => ['nullable', 'array'],
            'data_categories.*' => ['string', 'max:128'],
            'data_subjects' => ['nullable', 'array'],
            'data_subjects.*' => ['string', 'max:128'],
            'processing_operations' => ['nullable', 'string', 'max:20000'],
            'necessity_proportionality' => ['nullable', 'string', 'max:20000'],
            'overall_risk_score' => ['nullable', 'integer', 'min:1', 'max:25'],
            'overall_risk_level' => ['nullable', Rule::in(RiskLevel::values())],
            'mitigation_summary' => ['nullable', 'string', 'max:20000'],
            'review_due_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'string'],
        ];
    }
}

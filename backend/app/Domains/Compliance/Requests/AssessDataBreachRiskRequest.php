<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Compliance\Enums\DataBreachSeverity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssessDataBreachRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'risk_likelihood' => ['required', 'integer', 'min:1', 'max:5'],
            'risk_impact' => ['required', 'integer', 'min:1', 'max:5'],
            'risk_assessment_notes' => ['nullable', 'string', 'max:20000'],
            'impact_analysis' => ['nullable', 'string', 'max:20000'],
            'severity' => ['nullable', Rule::in(DataBreachSeverity::values())],
        ];
    }
}

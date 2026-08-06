<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Compliance\Enums\RiskCategory;
use App\Domains\Compliance\Enums\RiskLevel;
use App\Domains\Compliance\Enums\RiskRegisterStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRiskRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'category' => ['sometimes', 'required', Rule::in(RiskCategory::values())],
            'status' => ['sometimes', 'required', Rule::in(RiskRegisterStatus::values())],
            'likelihood' => ['nullable', 'integer', 'min:1', 'max:5'],
            'impact' => ['nullable', 'integer', 'min:1', 'max:5'],
            'risk_level' => ['nullable', Rule::in(RiskLevel::values())],
            'residual_likelihood' => ['nullable', 'integer', 'min:1', 'max:5'],
            'residual_impact' => ['nullable', 'integer', 'min:1', 'max:5'],
            'mitigation_plan' => ['nullable', 'string', 'max:20000'],
            'review_due_at' => ['nullable', 'date'],
            'owner_id' => ['nullable', 'string'],
        ];
    }
}

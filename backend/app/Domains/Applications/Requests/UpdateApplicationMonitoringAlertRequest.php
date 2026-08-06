<?php

namespace App\Domains\Applications\Requests;

use App\Domains\Applications\Enums\ApplicationMonitoringAlertOperator;
use App\Domains\Applications\Enums\ApplicationMonitoringAlertSeverity;
use App\Domains\Applications\Enums\ApplicationMonitoringMetric;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationMonitoringAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'metric' => ['sometimes', 'required', Rule::in(ApplicationMonitoringMetric::values())],
            'operator' => ['sometimes', 'required', Rule::in(ApplicationMonitoringAlertOperator::values())],
            'threshold' => ['sometimes', 'required', 'numeric'],
            'severity' => ['sometimes', 'required', Rule::in(ApplicationMonitoringAlertSeverity::values())],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'cooldown_minutes' => ['sometimes', 'required', 'integer', 'min:1', 'max:10080'],
            'message' => ['sometimes', 'nullable', 'string'],
        ];
    }
}

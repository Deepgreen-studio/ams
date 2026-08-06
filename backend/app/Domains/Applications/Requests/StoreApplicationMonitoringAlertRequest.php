<?php

namespace App\Domains\Applications\Requests;

use App\Domains\Applications\Enums\ApplicationMonitoringAlertOperator;
use App\Domains\Applications\Enums\ApplicationMonitoringAlertSeverity;
use App\Domains\Applications\Enums\ApplicationMonitoringMetric;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationMonitoringAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'metric' => ['required', Rule::in(ApplicationMonitoringMetric::values())],
            'operator' => ['nullable', Rule::in(ApplicationMonitoringAlertOperator::values())],
            'threshold' => ['required', 'numeric'],
            'severity' => ['nullable', Rule::in(ApplicationMonitoringAlertSeverity::values())],
            'is_active' => ['nullable', 'boolean'],
            'cooldown_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'message' => ['nullable', 'string'],
        ];
    }
}

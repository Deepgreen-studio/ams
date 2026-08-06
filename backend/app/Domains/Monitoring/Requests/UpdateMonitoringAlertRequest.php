<?php

namespace App\Domains\Monitoring\Requests;

use App\Domains\Monitoring\Enums\MonitoringMetric;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMonitoringAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'metric' => ['sometimes', Rule::in(MonitoringMetric::values())],
            'operator' => ['nullable', Rule::in(['gt', 'gte', 'lt', 'lte', 'eq'])],
            'threshold' => ['sometimes', 'numeric'],
            'is_enabled' => ['nullable', 'boolean'],
            'cooldown_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'channels' => ['nullable', 'array'],
            'channels.*' => ['string', 'max:50'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

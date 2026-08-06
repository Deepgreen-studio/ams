<?php

namespace App\Domains\Applications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IngestApplicationHealthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'application_version_id' => ['nullable', 'string'],
            'version_label' => ['nullable', 'string', 'max:64'],
            'recorded_at' => ['nullable', 'date'],
            'health_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'crash_rate' => ['nullable', 'numeric', 'min:0'],
            'anr_rate' => ['nullable', 'numeric', 'min:0'],
            'api_error_rate' => ['nullable', 'numeric', 'min:0'],
            'avg_response_time_ms' => ['nullable', 'integer', 'min:0'],
            'avg_memory_usage_mb' => ['nullable', 'numeric', 'min:0'],
            'avg_battery_usage' => ['nullable', 'numeric', 'min:0'],
            'crash_count' => ['nullable', 'integer', 'min:0'],
            'anr_count' => ['nullable', 'integer', 'min:0'],
            'api_error_count' => ['nullable', 'integer', 'min:0'],
            'sample_size' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

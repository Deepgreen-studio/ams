<?php

namespace App\Domains\Applications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IngestApplicationAnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'metric_date' => ['nullable', 'date'],
            'active_users' => ['nullable', 'integer', 'min:0'],
            'daily_users' => ['nullable', 'integer', 'min:0'],
            'monthly_users' => ['nullable', 'integer', 'min:0'],
            'avg_session_seconds' => ['nullable', 'integer', 'min:0'],
            'retention_d1' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'retention_d7' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'retention_d30' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'installs' => ['nullable', 'integer', 'min:0'],
            'uninstalls' => ['nullable', 'integer', 'min:0'],
            'sessions' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
            'countries' => ['nullable', 'array'],
            'countries.*.country_code' => ['required_with:countries', 'string', 'max:8'],
            'countries.*.country_name' => ['nullable', 'string', 'max:128'],
            'countries.*.users' => ['nullable', 'integer', 'min:0'],
            'countries.*.sessions' => ['nullable', 'integer', 'min:0'],
            'countries.*.installs' => ['nullable', 'integer', 'min:0'],
            'devices' => ['nullable', 'array'],
            'devices.*.device_model' => ['nullable', 'string', 'max:128'],
            'devices.*.os_name' => ['nullable', 'string', 'max:64'],
            'devices.*.os_version' => ['nullable', 'string', 'max:64'],
            'devices.*.users' => ['nullable', 'integer', 'min:0'],
            'devices.*.sessions' => ['nullable', 'integer', 'min:0'],
            'heatmap' => ['nullable', 'array'],
            'heatmap.*.day_of_week' => ['required_with:heatmap', 'integer', 'min:0', 'max:6'],
            'heatmap.*.hour' => ['required_with:heatmap', 'integer', 'min:0', 'max:23'],
            'heatmap.*.activity_count' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

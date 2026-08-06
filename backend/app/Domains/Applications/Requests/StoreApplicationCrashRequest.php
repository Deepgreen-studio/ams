<?php

namespace App\Domains\Applications\Requests;

use App\Domains\Applications\Enums\ApplicationCrashSeverity;
use App\Domains\Applications\Enums\ApplicationCrashType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationCrashRequest extends FormRequest
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
            'type' => ['nullable', Rule::in(ApplicationCrashType::values())],
            'severity' => ['nullable', Rule::in(ApplicationCrashSeverity::values())],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'stack_trace' => ['nullable', 'string'],
            'crash_log' => ['nullable', 'string'],
            'fingerprint' => ['nullable', 'string', 'max:128'],
            'device_id' => ['nullable', 'string', 'max:128'],
            'device_model' => ['nullable', 'string', 'max:128'],
            'device_manufacturer' => ['nullable', 'string', 'max:128'],
            'device_os' => ['nullable', 'string', 'max:64'],
            'device_os_version' => ['nullable', 'string', 'max:64'],
            'device_meta' => ['nullable', 'array'],
            'endpoint' => ['nullable', 'string', 'max:512'],
            'http_status' => ['nullable', 'integer', 'min:100', 'max:599'],
            'response_time_ms' => ['nullable', 'integer', 'min:0'],
            'memory_usage_mb' => ['nullable', 'numeric', 'min:0'],
            'battery_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'occurred_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

<?php

namespace App\Domains\Applications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IngestApplicationApiErrorRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'endpoint' => ['required', 'string', 'max:512'],
            'http_status' => ['nullable', 'integer', 'min:100', 'max:599'],
            'response_time_ms' => ['nullable', 'integer', 'min:0'],
            'stack_trace' => ['nullable', 'string'],
            'device_id' => ['nullable', 'string', 'max:128'],
            'device_model' => ['nullable', 'string', 'max:128'],
            'device_os' => ['nullable', 'string', 'max:64'],
            'device_os_version' => ['nullable', 'string', 'max:64'],
            'occurred_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

<?php

namespace App\Domains\Applications\Requests;

use App\Domains\Applications\Enums\ApplicationEnvironmentHealthStatus;
use App\Domains\Applications\Enums\ApplicationEnvironmentStatus;
use App\Domains\Applications\Enums\ApplicationEnvironmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationEnvironmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash:ascii'],
            'type' => ['sometimes', 'required', Rule::in(ApplicationEnvironmentType::values())],
            'api_url' => ['nullable', 'url', 'max:500'],
            'web_url' => ['nullable', 'url', 'max:500'],
            'status' => ['sometimes', 'required', Rule::in(ApplicationEnvironmentStatus::values())],
            'health_status' => ['nullable', Rule::in(ApplicationEnvironmentHealthStatus::values())],
            'is_current' => ['nullable', 'boolean'],
            'variables' => ['nullable', 'array'],
            'variables.*.key' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z_][A-Za-z0-9_]*$/'],
            'variables.*.value' => ['nullable', 'string', 'max:5000'],
            'variables.*.keep_existing' => ['nullable', 'boolean'],
        ];
    }
}

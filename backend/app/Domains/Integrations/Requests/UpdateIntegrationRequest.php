<?php

namespace App\Domains\Integrations\Requests;

use App\Domains\Integrations\Enums\IntegrationAuthenticationType;
use App\Domains\Integrations\Enums\IntegrationHealthStatus;
use App\Domains\Integrations\Enums\IntegrationStatus;
use App\Domains\Integrations\Enums\IntegrationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIntegrationRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['sometimes', 'required', Rule::in(IntegrationType::values())],
            'status' => ['sometimes', 'required', Rule::in(IntegrationStatus::values())],
            'authentication_type' => ['sometimes', 'required', Rule::in(IntegrationAuthenticationType::values())],
            'base_url' => ['nullable', 'url', 'max:500'],
            'api_version' => ['nullable', 'string', 'max:32'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:300'],
            'retry_attempts' => ['nullable', 'integer', 'min:0', 'max:10'],
            'health_status' => ['nullable', Rule::in(IntegrationHealthStatus::values())],
        ];
    }
}

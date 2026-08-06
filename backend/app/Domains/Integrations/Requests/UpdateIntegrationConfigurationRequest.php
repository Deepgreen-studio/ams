<?php

namespace App\Domains\Integrations\Requests;

use App\Domains\Integrations\Enums\IntegrationAuthenticationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIntegrationConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_url' => ['nullable', 'url', 'max:500'],
            'api_version' => ['nullable', 'string', 'max:32'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:300'],
            'retry_attempts' => ['nullable', 'integer', 'min:0', 'max:10'],
            'default_headers' => ['nullable', 'array'],
            'default_headers.*' => ['nullable', 'string', 'max:2000'],
            'default_query' => ['nullable', 'array'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'health_check_path' => ['nullable', 'string', 'max:500'],
            'authentication_type' => ['nullable', Rule::in(IntegrationAuthenticationType::values())],
            'credentials' => ['nullable', 'array'],
            'credentials.api_key' => ['nullable', 'string', 'max:2000'],
            'credentials.api_key_header' => ['nullable', 'string', 'max:100'],
            'credentials.api_key_location' => ['nullable', Rule::in(['header', 'query'])],
            'credentials.api_key_query' => ['nullable', 'string', 'max:100'],
            'credentials.bearer_token' => ['nullable', 'string', 'max:5000'],
            'credentials.username' => ['nullable', 'string', 'max:255'],
            'credentials.password' => ['nullable', 'string', 'max:255'],
            'credentials.jwt_token' => ['nullable', 'string', 'max:5000'],
            'credentials.jwt_header' => ['nullable', 'string', 'max:100'],
            'credentials.jwt_prefix' => ['nullable', 'string', 'max:50'],
            'credentials.oauth_access_token' => ['nullable', 'string', 'max:5000'],
            'credentials.oauth_token_type' => ['nullable', 'string', 'max:50'],
            'credentials.access_token' => ['nullable', 'string', 'max:5000'],
            'clear_credentials' => ['nullable', 'boolean'],
        ];
    }
}

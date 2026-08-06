<?php

namespace App\Domains\Settings\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecuritySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'password_min_length' => ['sometimes', 'integer', 'min:6', 'max:128'],
            'password_require_symbols' => ['sometimes', 'boolean'],
            'session_timeout_minutes' => ['sometimes', 'integer', 'min:5', 'max:10080'],
            'login_max_attempts' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'api_rate_limit' => ['sometimes', 'integer', 'min:10', 'max:10000'],
        ];
    }
}

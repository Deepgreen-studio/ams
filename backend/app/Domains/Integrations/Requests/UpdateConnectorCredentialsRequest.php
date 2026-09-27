<?php

namespace App\Domains\Integrations\Requests;

use App\Domains\Integrations\Services\CredentialVault;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConnectorCredentialsRequest extends FormRequest
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
        $rules = [
            'credentials' => ['required', 'array'],
        ];

        foreach (CredentialVault::ALLOWED_KEYS as $key) {
            $rules['credentials.' . $key] = ['nullable', 'string', 'max:5000'];
        }

        $rules['credentials.api_key_location'] = ['nullable', 'string', Rule::in(['header', 'query'])];
        $rules['credentials.token_url'] = ['nullable', 'url', 'max:500'];

        return $rules;
    }
}

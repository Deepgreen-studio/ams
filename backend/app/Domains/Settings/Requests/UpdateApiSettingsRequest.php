<?php

namespace App\Domains\Settings\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApiSettingsRequest extends FormRequest
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
            'enabled' => ['sometimes', 'boolean'],
            'default_page_size' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'max_page_size' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'token_expiration_minutes' => ['sometimes', 'integer', 'min:5', 'max:525600'],
        ];
    }
}

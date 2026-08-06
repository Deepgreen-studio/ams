<?php

namespace App\Domains\Settings\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingsRequest extends FormRequest
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
            'app_name' => ['sometimes', 'string', 'max:255'],
            'app_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'timezone' => ['sometimes', 'timezone'],
            'language' => ['sometimes', 'string', 'max:16'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'date_format' => ['sometimes', 'string', 'max:32'],
            'time_format' => ['sometimes', 'string', 'max:32'],
            'maintenance_mode' => ['sometimes', 'boolean'],
        ];
    }
}

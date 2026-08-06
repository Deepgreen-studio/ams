<?php

namespace App\Domains\Notifications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationChannelRequest extends FormRequest
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
            'is_enabled' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'config' => ['nullable', 'array'],
        ];
    }
}

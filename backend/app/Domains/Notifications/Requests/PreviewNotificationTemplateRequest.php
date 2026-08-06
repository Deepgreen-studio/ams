<?php

namespace App\Domains\Notifications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewNotificationTemplateRequest extends FormRequest
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
            'variables' => ['nullable', 'array'],
        ];
    }
}

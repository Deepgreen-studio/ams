<?php

namespace App\Domains\Integrations\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_name' => ['nullable', 'string', 'max:150'],
            'payload' => ['nullable', 'array'],
        ];
    }
}

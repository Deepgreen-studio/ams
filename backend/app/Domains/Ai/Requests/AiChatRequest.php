<?php

namespace App\Domains\Ai\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiChatRequest extends FormRequest
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
            'message' => ['required', 'string', 'max:20000'],
            'conversation_id' => ['nullable', 'string'],
            'provider_id' => ['nullable', 'string'],
            'company_id' => ['nullable'],
            'model' => ['nullable', 'string', 'max:120'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['nullable', 'integer', 'min:1', 'max:128000'],
            'title' => ['nullable', 'string', 'max:255'],
            'context_type' => ['nullable', 'string', 'max:120'],
            'context_id' => ['nullable', 'string', 'max:64'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

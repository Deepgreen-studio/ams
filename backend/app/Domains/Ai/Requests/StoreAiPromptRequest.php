<?php

namespace App\Domains\Ai\Requests;

use App\Domains\Ai\Enums\AiFeature;
use App\Domains\Ai\Enums\AiPromptStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiPromptRequest extends FormRequest
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
            'company_id' => ['nullable'],
            'key' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'feature' => ['required', 'string', Rule::in(AiFeature::values())],
            'system_prompt' => ['nullable', 'string'],
            'user_template' => ['nullable', 'string'],
            'model_override' => ['nullable', 'string', 'max:120'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['nullable', 'integer', 'min:1', 'max:128000'],
            'output_schema' => ['nullable', 'array'],
            'version' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', Rule::in(AiPromptStatus::values())],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

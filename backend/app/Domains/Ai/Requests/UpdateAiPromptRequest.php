<?php

namespace App\Domains\Ai\Requests;

use App\Domains\Ai\Enums\AiFeature;
use App\Domains\Ai\Enums\AiPromptStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiPromptRequest extends FormRequest
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
            'company_id' => ['sometimes', 'nullable'],
            'key' => ['sometimes', 'nullable', 'string', 'max:100'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'feature' => ['sometimes', 'required', 'string', Rule::in(AiFeature::values())],
            'system_prompt' => ['sometimes', 'nullable', 'string'],
            'user_template' => ['sometimes', 'nullable', 'string'],
            'model_override' => ['sometimes', 'nullable', 'string', 'max:120'],
            'temperature' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:128000'],
            'output_schema' => ['sometimes', 'nullable', 'array'],
            'version' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(AiPromptStatus::values())],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

<?php

namespace App\Domains\Ai\Requests;

use App\Domains\Ai\Enums\AiProviderDriver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiProviderRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'driver' => ['sometimes', 'required', 'string', Rule::in(AiProviderDriver::values())],
            'status' => ['sometimes', 'nullable', 'string', 'max:32'],
            'base_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'default_model' => ['sometimes', 'nullable', 'string', 'max:120'],
            'embedding_model' => ['sometimes', 'nullable', 'string', 'max:120'],
            'authentication_type' => ['sometimes', 'nullable', 'string', 'max:32'],
            'credentials' => ['sometimes', 'nullable', 'array'],
            'config' => ['sometimes', 'nullable', 'array'],
            'timeout_seconds' => ['sometimes', 'nullable', 'integer', 'min:5', 'max:300'],
            'retry_attempts' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:10'],
            'is_default' => ['sometimes', 'boolean'],
            'is_enabled' => ['sometimes', 'boolean'],
        ];
    }
}

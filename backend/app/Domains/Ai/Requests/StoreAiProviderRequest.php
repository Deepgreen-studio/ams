<?php

namespace App\Domains\Ai\Requests;

use App\Domains\Ai\Enums\AiProviderDriver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiProviderRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:64'],
            'driver' => ['required', 'string', Rule::in(AiProviderDriver::values())],
            'status' => ['nullable', 'string', 'max:32'],
            'base_url' => ['nullable', 'url', 'max:255'],
            'default_model' => ['nullable', 'string', 'max:120'],
            'embedding_model' => ['nullable', 'string', 'max:120'],
            'authentication_type' => ['nullable', 'string', 'max:32'],
            'credentials' => ['nullable', 'array'],
            'config' => ['nullable', 'array'],
            'timeout_seconds' => ['nullable', 'integer', 'min:5', 'max:300'],
            'retry_attempts' => ['nullable', 'integer', 'min:0', 'max:10'],
            'is_default' => ['nullable', 'boolean'],
            'is_enabled' => ['nullable', 'boolean'],
        ];
    }
}

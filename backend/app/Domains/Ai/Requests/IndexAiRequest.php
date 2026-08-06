<?php

namespace App\Domains\Ai\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexAiRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable'],
            'driver' => ['nullable', 'string', 'max:64'],
            'feature' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'max:32'],
            'operation' => ['nullable', 'string', 'max:64'],
            'is_enabled' => ['nullable'],
            'user_id' => ['nullable'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->validated();
    }
}

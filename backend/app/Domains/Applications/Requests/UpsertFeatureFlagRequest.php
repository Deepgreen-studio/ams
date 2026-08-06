<?php

namespace App\Domains\Applications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertFeatureFlagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z_][A-Za-z0-9_.-]*$/'],
            'enabled' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'rollout' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}

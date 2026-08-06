<?php

namespace App\Domains\Applications\Requests;

use App\Domains\Applications\Enums\ApplicationConfigurationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'payload' => ['sometimes', 'required', 'array'],
            'status' => ['sometimes', 'required', Rule::in(ApplicationConfigurationStatus::values())],
            'is_active' => ['nullable', 'boolean'],
            'change_summary' => ['nullable', 'string', 'max:500'],
        ];
    }
}

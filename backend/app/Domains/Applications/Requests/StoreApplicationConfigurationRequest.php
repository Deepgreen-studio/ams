<?php

namespace App\Domains\Applications\Requests;

use App\Domains\Applications\Enums\ApplicationConfigurationStatus;
use App\Domains\Applications\Enums\ApplicationConfigurationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'environment_id' => ['nullable', 'string'],
            'type' => ['required', Rule::in(ApplicationConfigurationType::values())],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'payload' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(ApplicationConfigurationStatus::values())],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

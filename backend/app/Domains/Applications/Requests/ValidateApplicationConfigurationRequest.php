<?php

namespace App\Domains\Applications\Requests;

use App\Domains\Applications\Enums\ApplicationConfigurationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidateApplicationConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(ApplicationConfigurationType::values())],
            'payload' => ['required', 'array'],
        ];
    }
}

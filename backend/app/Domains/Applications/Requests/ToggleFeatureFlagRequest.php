<?php

namespace App\Domains\Applications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToggleFeatureFlagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
        ];
    }
}

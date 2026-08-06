<?php

namespace App\Domains\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompletePrivacyRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

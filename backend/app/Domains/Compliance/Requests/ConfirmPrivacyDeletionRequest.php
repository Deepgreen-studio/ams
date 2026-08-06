<?php

namespace App\Domains\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPrivacyDeletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'confirmed' => ['required', 'accepted'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

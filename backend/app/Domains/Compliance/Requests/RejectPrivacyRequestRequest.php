<?php

namespace App\Domains\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectPrivacyRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['required', 'string', 'max:5000'],
        ];
    }
}

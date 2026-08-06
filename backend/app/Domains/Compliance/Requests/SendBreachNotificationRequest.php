<?php

namespace App\Domains\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendBreachNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:20000'],
            'regulator_reference' => ['nullable', 'string', 'max:128'],
        ];
    }
}

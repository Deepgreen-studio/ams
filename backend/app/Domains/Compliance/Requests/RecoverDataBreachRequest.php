<?php

namespace App\Domains\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecoverDataBreachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recovery_summary' => ['required', 'string', 'max:20000'],
        ];
    }
}

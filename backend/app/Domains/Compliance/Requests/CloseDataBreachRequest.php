<?php

namespace App\Domains\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseDataBreachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comments' => ['nullable', 'string', 'max:5000'],
            'lessons_learned' => ['nullable', 'string', 'max:20000'],
        ];
    }
}

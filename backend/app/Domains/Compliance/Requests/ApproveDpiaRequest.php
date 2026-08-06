<?php

namespace App\Domains\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveDpiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approval_notes' => ['nullable', 'string', 'max:5000'],
            'next_review_at' => ['nullable', 'date'],
        ];
    }
}

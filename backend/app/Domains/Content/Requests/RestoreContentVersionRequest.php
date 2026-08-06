<?php

namespace App\Domains\Content\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestoreContentVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}

<?php

namespace App\Domains\Customers\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RevokeLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

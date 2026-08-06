<?php

namespace App\Domains\Content\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompareContentVersionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'string'],
            'to' => ['required', 'string', 'different:from'],
        ];
    }
}

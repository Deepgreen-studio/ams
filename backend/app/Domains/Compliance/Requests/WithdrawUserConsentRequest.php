<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Compliance\Enums\ConsentSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WithdrawUserConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source' => ['nullable', Rule::in(ConsentSource::values())],
            'device' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

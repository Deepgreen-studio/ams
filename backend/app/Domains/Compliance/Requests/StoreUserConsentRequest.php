<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Compliance\Enums\ConsentSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'string'],
            'consent_type_id' => ['required', 'string'],
            'user_id' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'string'],
            'subject_email' => ['nullable', 'email', 'max:255'],
            'subject_name' => ['nullable', 'string', 'max:255'],
            'granted' => ['nullable', 'boolean'],
            'consent_version' => ['nullable', 'string', 'max:32'],
            'source' => ['nullable', Rule::in(ConsentSource::values())],
            'device' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

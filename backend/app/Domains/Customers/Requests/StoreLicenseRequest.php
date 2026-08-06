<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Customers\Enums\LicenseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_id' => ['required', 'string'],
            'customer_application_id' => ['nullable', 'string'],
            'license_key' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(LicenseStatus::values())],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'features' => ['nullable'],
            'max_activations' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

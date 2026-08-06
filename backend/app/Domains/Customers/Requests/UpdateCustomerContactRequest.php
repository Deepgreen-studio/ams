<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Customers\Enums\CustomerContactStatus;
use App\Domains\Customers\Enums\CustomerContactType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contact_type' => ['sometimes', 'required', Rule::in(CustomerContactType::values())],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-\(\)]{7,30}$/'],
            'position' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'required', Rule::in(CustomerContactStatus::values())],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

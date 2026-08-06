<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Customers\Enums\CustomerApplicationOwnershipType;
use App\Domains\Customers\Enums\CustomerApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerApplicationRequest extends FormRequest
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
            'application_environment_id' => ['nullable', 'string'],
            'environment_id' => ['nullable', 'string'],
            'integration_id' => ['nullable', 'string'],
            'owner_contact_id' => ['nullable', 'string'],
            'ownership_type' => ['sometimes', 'required', Rule::in(CustomerApplicationOwnershipType::values())],
            'status' => ['sometimes', 'required', Rule::in(CustomerApplicationStatus::values())],
            'activated_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Customers\Enums\CustomerApplicationOwnershipType;
use App\Domains\Customers\Enums\CustomerApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCustomerApplicationRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(CustomerApplicationStatus::values())],
            'ownership_type' => ['nullable', Rule::in(CustomerApplicationOwnershipType::values())],
            'customer' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'string'],
            'application' => ['nullable', 'string'],
            'application_id' => ['nullable', 'string'],
            'sort_by' => ['nullable', 'string', 'max:50'],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'trashed' => ['nullable', Rule::in(['with', 'only', ''])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->validated();
    }
}

<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Customers\Enums\CustomerContactStatus;
use App\Domains\Customers\Enums\CustomerContactType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCustomerContactRequest extends FormRequest
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
            'status' => ['nullable', Rule::in(CustomerContactStatus::values())],
            'contact_type' => ['nullable', Rule::in(CustomerContactType::values())],
            'customer' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'string'],
            'department' => ['nullable', 'string', 'max:120'],
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

<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Customers\Enums\CustomerDocumentCategory;
use App\Domains\Customers\Enums\CustomerDocumentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCustomerDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(CustomerDocumentCategory::values())],
            'status' => ['nullable', Rule::in(CustomerDocumentStatus::values())],
            'customer' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'string'],
            'is_current' => ['nullable'],
            'include_versions' => ['nullable'],
            'expiring_soon' => ['nullable'],
            'sort_by' => ['nullable', 'string', 'max:50'],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'trashed' => ['nullable', Rule::in(['with', 'only', ''])],
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }
}

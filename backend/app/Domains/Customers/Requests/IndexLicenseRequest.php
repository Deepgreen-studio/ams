<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Customers\Enums\LicenseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(LicenseStatus::values())],
            'customer' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'string'],
            'subscription' => ['nullable', 'string'],
            'subscription_id' => ['nullable', 'string'],
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

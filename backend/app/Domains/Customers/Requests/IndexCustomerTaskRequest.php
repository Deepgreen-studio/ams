<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Customers\Enums\CustomerTaskPriority;
use App\Domains\Customers\Enums\CustomerTaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCustomerTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(CustomerTaskStatus::values())],
            'priority' => ['nullable', Rule::in(CustomerTaskPriority::values())],
            'customer' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'string'],
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

<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Customers\Enums\CustomerCommunicationDirection;
use App\Domains\Customers\Enums\CustomerCommunicationStatus;
use App\Domains\Customers\Enums\CustomerCommunicationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCustomerCommunicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(CustomerCommunicationType::values())],
            'direction' => ['nullable', Rule::in(CustomerCommunicationDirection::values())],
            'status' => ['nullable', Rule::in(CustomerCommunicationStatus::values())],
            'customer' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'string'],
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

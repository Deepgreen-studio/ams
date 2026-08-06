<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Customers\Enums\CustomerDocumentCategory;
use App\Domains\Customers\Enums\CustomerDocumentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::in(CustomerDocumentCategory::values())],
            'status' => ['nullable', Rule::in(CustomerDocumentStatus::values())],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'file' => ['required', 'file', 'max:51200'],
        ];
    }
}

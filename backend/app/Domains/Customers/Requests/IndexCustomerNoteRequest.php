<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Customers\Enums\CustomerNoteStatus;
use App\Domains\Customers\Enums\CustomerNoteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCustomerNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'note_type' => ['nullable', Rule::in(CustomerNoteType::values())],
            'status' => ['nullable', Rule::in(CustomerNoteStatus::values())],
            'customer' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'string'],
            'pinned' => ['nullable'],
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

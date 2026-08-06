<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Customers\Enums\CustomerNoteStatus;
use App\Domains\Customers\Enums\CustomerNoteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'string'],
            'note_type' => ['required', Rule::in(CustomerNoteType::values())],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'is_pinned' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(CustomerNoteStatus::values())],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}

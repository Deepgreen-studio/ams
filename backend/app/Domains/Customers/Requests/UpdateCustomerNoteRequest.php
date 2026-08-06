<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Customers\Enums\CustomerNoteStatus;
use App\Domains\Customers\Enums\CustomerNoteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note_type' => ['sometimes', 'required', Rule::in(CustomerNoteType::values())],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['sometimes', 'required', 'string', 'max:20000'],
            'is_pinned' => ['nullable', 'boolean'],
            'status' => ['sometimes', 'required', Rule::in(CustomerNoteStatus::values())],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}

<?php

namespace App\Domains\Support\Requests;

use App\Domains\Support\Enums\SupportTicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionSupportTicketRequest extends FormRequest
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
            'status' => ['required', Rule::in(SupportTicketStatus::values())],
            'comments' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

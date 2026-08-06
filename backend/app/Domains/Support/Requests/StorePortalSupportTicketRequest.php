<?php

namespace App\Domains\Support\Requests;

use App\Domains\Support\Enums\SupportTicketCategory;
use App\Domains\Support\Enums\SupportTicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePortalSupportTicketRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'category' => ['required', 'string', Rule::in(SupportTicketCategory::values())],
            'priority' => ['nullable', 'string', Rule::in(SupportTicketPriority::values())],
            'application_id' => ['nullable', 'string'],
        ];
    }
}

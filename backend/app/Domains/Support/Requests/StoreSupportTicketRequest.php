<?php

namespace App\Domains\Support\Requests;

use App\Domains\Support\Enums\SupportTicketCategory;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketSource;
use App\Domains\Support\Enums\SupportTicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
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
            'company_id' => ['required', 'string'],
            'customer_id' => ['nullable', 'string'],
            'application_id' => ['nullable', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'priority' => ['nullable', Rule::in(SupportTicketPriority::values())],
            'category' => ['required', Rule::in(SupportTicketCategory::values())],
            'status' => ['nullable', Rule::in(SupportTicketStatus::values())],
            'assigned_to' => ['nullable', 'string'],
            'source' => ['nullable', Rule::in(SupportTicketSource::values())],
            'involves_personal_data' => ['nullable', 'boolean'],
        ];
    }
}

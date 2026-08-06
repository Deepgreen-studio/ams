<?php

namespace App\Domains\Support\Requests;

use App\Domains\Support\Enums\SupportTicketCategory;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketSource;
use App\Domains\Support\Enums\SupportTicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportTicketRequest extends FormRequest
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
            'customer_id' => ['sometimes', 'nullable', 'string'],
            'application_id' => ['sometimes', 'nullable', 'string'],
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:20000'],
            'priority' => ['sometimes', 'required', Rule::in(SupportTicketPriority::values())],
            'category' => ['sometimes', 'required', Rule::in(SupportTicketCategory::values())],
            'status' => ['sometimes', 'required', Rule::in(SupportTicketStatus::values())],
            'assigned_to' => ['sometimes', 'nullable', 'string'],
            'source' => ['sometimes', 'required', Rule::in(SupportTicketSource::values())],
        ];
    }
}

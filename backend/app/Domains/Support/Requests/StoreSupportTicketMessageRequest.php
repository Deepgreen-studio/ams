<?php

namespace App\Domains\Support\Requests;

use App\Domains\Support\Enums\SupportTicketAttachmentType;
use App\Domains\Support\Enums\SupportTicketMessageVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketMessageRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:100000'],
            'body_format' => ['nullable', Rule::in(['html', 'plain'])],
            'visibility' => ['required', Rule::in(SupportTicketMessageVisibility::values())],
            'attachment_type' => ['nullable', Rule::in(SupportTicketAttachmentType::values())],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:102400'],
        ];
    }
}

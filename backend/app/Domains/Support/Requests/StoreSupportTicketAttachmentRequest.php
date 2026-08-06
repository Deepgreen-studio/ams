<?php

namespace App\Domains\Support\Requests;

use App\Domains\Support\Enums\SupportTicketAttachmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketAttachmentRequest extends FormRequest
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
            'message_id' => ['nullable', 'string'],
            'attachment_type' => ['nullable', Rule::in(SupportTicketAttachmentType::values())],
            'attachments' => ['required', 'array', 'min:1', 'max:10'],
            'attachments.*' => ['file', 'max:102400'],
        ];
    }
}

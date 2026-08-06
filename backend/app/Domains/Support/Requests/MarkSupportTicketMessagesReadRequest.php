<?php

namespace App\Domains\Support\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkSupportTicketMessagesReadRequest extends FormRequest
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
            'message_ids' => ['nullable', 'array'],
            'message_ids.*' => ['string'],
        ];
    }
}

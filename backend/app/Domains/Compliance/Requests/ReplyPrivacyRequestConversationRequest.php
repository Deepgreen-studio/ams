<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Support\Enums\SupportTicketMessageVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReplyPrivacyRequestConversationRequest extends FormRequest
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
            'visibility' => ['nullable', Rule::in(SupportTicketMessageVisibility::values())],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('visibility')) {
            $this->merge(['visibility' => SupportTicketMessageVisibility::Public->value]);
        }
    }
}

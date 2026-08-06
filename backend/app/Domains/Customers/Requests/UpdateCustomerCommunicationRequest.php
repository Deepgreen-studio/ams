<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Customers\Enums\CustomerCommunicationDirection;
use App\Domains\Customers\Enums\CustomerCommunicationStatus;
use App\Domains\Customers\Enums\CustomerCommunicationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerCommunicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'required', Rule::in(CustomerCommunicationType::values())],
            'direction' => ['sometimes', 'required', Rule::in(CustomerCommunicationDirection::values())],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:50000'],
            'status' => ['sometimes', 'required', Rule::in(CustomerCommunicationStatus::values())],
            'channel_reference' => ['nullable', 'string', 'max:255'],
            'participants' => ['nullable'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}

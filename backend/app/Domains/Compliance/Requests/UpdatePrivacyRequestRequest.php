<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Compliance\Enums\PrivacyIdentityVerificationStatus;
use App\Domains\Compliance\Enums\PrivacyRequestStatus;
use App\Domains\Compliance\Enums\PrivacyRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePrivacyRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_type' => ['sometimes', 'required', Rule::in(PrivacyRequestType::values())],
            'requester_name' => ['sometimes', 'required', 'string', 'max:255'],
            'requester_email' => ['sometimes', 'required', 'email', 'max:255'],
            'requester_phone' => ['nullable', 'string', 'max:64'],
            'customer_id' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['sometimes', 'required', Rule::in(PrivacyRequestStatus::values())],
            'identity_verification_status' => ['sometimes', 'required', Rule::in(PrivacyIdentityVerificationStatus::values())],
            'assigned_to' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}

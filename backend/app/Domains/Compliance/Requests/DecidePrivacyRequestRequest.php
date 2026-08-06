<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Compliance\Enums\PrivacyRequestDecision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecidePrivacyRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['nullable', Rule::in([
                PrivacyRequestDecision::Approved->value,
                PrivacyRequestDecision::PartiallyApproved->value,
            ])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

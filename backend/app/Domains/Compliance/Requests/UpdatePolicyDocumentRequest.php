<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Compliance\Enums\PolicyDocumentStatus;
use App\Domains\Compliance\Enums\PolicyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePolicyDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:191'],
            'policy_type' => ['sometimes', 'required', Rule::in(PolicyType::values())],
            'description' => ['nullable', 'string', 'max:20000'],
            'body' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(PolicyDocumentStatus::values())],
            'content_id' => ['nullable', 'string'],
            'effective_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'review_due_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'string'],
            'change_summary' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}

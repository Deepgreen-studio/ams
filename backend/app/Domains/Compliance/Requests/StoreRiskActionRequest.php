<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Compliance\Enums\RiskActionStatus;
use App\Domains\Compliance\Enums\RiskActionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRiskActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action_type' => ['nullable', Rule::in(RiskActionType::values())],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['nullable', Rule::in(RiskActionStatus::values())],
            'performed_by' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

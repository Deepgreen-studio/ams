<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Compliance\Enums\BreachActionStatus;
use App\Domains\Compliance\Enums\BreachActionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBreachActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action_type' => ['required', Rule::in(BreachActionType::values())],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['nullable', Rule::in(BreachActionStatus::values())],
            'performed_by' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

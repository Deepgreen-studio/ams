<?php

namespace App\Domains\Applications\Requests;

use App\Domains\Applications\Enums\ApplicationCrashSeverity;
use App\Domains\Applications\Enums\ApplicationCrashStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationCrashRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', Rule::in(ApplicationCrashStatus::values())],
            'severity' => ['sometimes', 'required', Rule::in(ApplicationCrashSeverity::values())],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'message' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

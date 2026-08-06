<?php

namespace App\Domains\Applications\Requests;

use App\Domains\Applications\Enums\ApplicationVersionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version_number' => ['sometimes', 'nullable', 'string', 'max:64'],
            'major' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'minor' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'patch' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'build_number' => ['nullable', 'string', 'max:64'],
            'status' => ['sometimes', 'required', Rule::in(ApplicationVersionStatus::values())],
            'release_date' => ['nullable', 'date'],
            'minimum_supported_version' => ['nullable', 'string', 'max:64'],
            'release_notes' => ['nullable', 'string', 'max:20000'],
        ];
    }
}

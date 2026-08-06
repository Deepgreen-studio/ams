<?php

namespace App\Domains\Applications\Requests;

use App\Domains\Applications\Enums\ApplicationVersionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version_number' => ['nullable', 'string', 'max:64', 'required_without_all:major,minor,patch'],
            'major' => ['nullable', 'integer', 'min:0', 'required_without:version_number', 'required_with:minor,patch'],
            'minor' => ['nullable', 'integer', 'min:0', 'required_without:version_number', 'required_with:major,patch'],
            'patch' => ['nullable', 'integer', 'min:0', 'required_without:version_number', 'required_with:major,minor'],
            'build_number' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', Rule::in(ApplicationVersionStatus::values())],
            'release_date' => ['nullable', 'date'],
            'minimum_supported_version' => ['nullable', 'string', 'max:64'],
            'release_notes' => ['nullable', 'string', 'max:20000'],
        ];
    }
}

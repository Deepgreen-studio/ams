<?php

namespace App\Domains\Applications\Requests;

use App\Domains\Applications\Enums\ApplicationReleaseNoteAudience;
use App\Domains\Applications\Enums\ApplicationReleaseStatus;
use App\Domains\Applications\Enums\ApplicationReleaseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationReleaseRequest extends FormRequest
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
            'application_version_id' => ['sometimes', 'nullable', 'string'],
            'environment_id' => ['sometimes', 'nullable', 'string'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'release_type' => ['sometimes', 'required', Rule::in(ApplicationReleaseType::values())],
            'status' => ['sometimes', 'required', Rule::in([ApplicationReleaseStatus::Cancelled->value])],
            'scheduled_at' => ['sometimes', 'nullable', 'date'],
            'deployment_date' => ['sometimes', 'nullable', 'date'],
            'plan_summary' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'notes' => ['sometimes', 'nullable', 'array'],
            'notes.*.title' => ['required_with:notes', 'string', 'max:255'],
            'notes.*.content' => ['nullable', 'string'],
            'notes.*.locale' => ['nullable', 'string', 'max:16'],
            'notes.*.audience' => ['nullable', Rule::in(ApplicationReleaseNoteAudience::values())],
            'notes.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

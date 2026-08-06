<?php

namespace App\Domains\Applications\Requests;

use App\Domains\Applications\Enums\ApplicationReleaseNoteAudience;
use App\Domains\Applications\Enums\ApplicationReleaseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationReleaseRequest extends FormRequest
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
            'application_version_id' => ['required', 'string'],
            'environment_id' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'release_type' => ['nullable', Rule::in(ApplicationReleaseType::values())],
            'requires_approval' => ['nullable', 'boolean'],
            'scheduled_at' => ['nullable', 'date'],
            'deployment_date' => ['nullable', 'date'],
            'plan_summary' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'notes' => ['nullable', 'array'],
            'notes.*.title' => ['required_with:notes', 'string', 'max:255'],
            'notes.*.content' => ['nullable', 'string'],
            'notes.*.locale' => ['nullable', 'string', 'max:16'],
            'notes.*.audience' => ['nullable', Rule::in(ApplicationReleaseNoteAudience::values())],
            'notes.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

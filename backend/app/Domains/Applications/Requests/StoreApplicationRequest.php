<?php

namespace App\Domains\Applications\Requests;

use App\Domains\Applications\Enums\ApplicationCategory;
use App\Domains\Applications\Enums\ApplicationPlatform;
use App\Domains\Applications\Enums\ApplicationStatus;
use App\Domains\Applications\Enums\ApplicationVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'string'],
            'integration_id' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash:ascii'],
            'description' => ['nullable', 'string', 'max:5000'],
            'platform' => ['required', Rule::in(ApplicationPlatform::values())],
            'category' => ['nullable', Rule::in(ApplicationCategory::values())],
            'icon' => ['nullable', 'string', 'max:500'],
            'banner' => ['nullable', 'string', 'max:500'],
            'current_version' => ['nullable', 'string', 'max:64'],
            'minimum_supported_version' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', Rule::in(ApplicationStatus::values())],
            'visibility' => ['nullable', Rule::in(ApplicationVisibility::values())],
        ];
    }
}

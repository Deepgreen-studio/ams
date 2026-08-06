<?php

namespace App\Domains\Integrations\Requests;

use App\Domains\Integrations\Enums\DataMappingDirection;
use App\Domains\Integrations\Enums\DataMappingStatus;
use App\Domains\Integrations\Enums\MappingTransformType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDataMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash:ascii'],
            'description' => ['nullable', 'string', 'max:5000'],
            'direction' => ['sometimes', Rule::in(DataMappingDirection::values())],
            'status' => ['sometimes', Rule::in(DataMappingStatus::values())],
            'source_entity' => ['sometimes', 'string', 'max:150'],
            'target_entity' => ['nullable', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
            'external_schema' => ['nullable', 'array'],
            'sample_payload' => ['nullable', 'array'],
            'options' => ['nullable', 'array'],
            'fields' => ['sometimes', 'array', 'min:1'],
            'fields.*.external_field' => ['required_with:fields', 'string', 'max:255'],
            'fields.*.internal_field' => ['required_with:fields', 'string', 'max:255'],
            'fields.*.transform_type' => ['nullable', Rule::in(MappingTransformType::values())],
            'fields.*.transform_config' => ['nullable', 'array'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.default_value' => ['nullable'],
            'fields.*.custom_rules' => ['nullable', 'array'],
            'fields.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'fields.*.is_enabled' => ['nullable', 'boolean'],
            'fields.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

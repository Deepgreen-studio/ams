<?php

namespace App\Domains\Integrations\Requests;

use App\Domains\Integrations\Enums\DataMappingDirection;
use App\Domains\Integrations\Enums\DataMappingStatus;
use App\Domains\Integrations\Enums\MappingTransformType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDataMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'string'],
            'integration_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash:ascii'],
            'description' => ['nullable', 'string', 'max:5000'],
            'direction' => ['nullable', Rule::in(DataMappingDirection::values())],
            'status' => ['nullable', Rule::in(DataMappingStatus::values())],
            'source_entity' => ['required', 'string', 'max:150'],
            'target_entity' => ['nullable', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
            'external_schema' => ['nullable', 'array'],
            'sample_payload' => ['nullable', 'array'],
            'options' => ['nullable', 'array'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.external_field' => ['required', 'string', 'max:255'],
            'fields.*.internal_field' => ['required', 'string', 'max:255'],
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

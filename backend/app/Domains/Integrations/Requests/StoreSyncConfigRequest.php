<?php

namespace App\Domains\Integrations\Requests;

use App\Domains\Integrations\Enums\SyncConflictStrategy;
use App\Domains\Integrations\Enums\SyncDirection;
use App\Domains\Integrations\Enums\SyncMode;
use App\Domains\Integrations\Enums\SyncTrigger;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSyncConfigRequest extends FormRequest
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
            'direction' => ['required', Rule::in(SyncDirection::values())],
            'default_mode' => ['nullable', Rule::in(SyncMode::values())],
            'trigger_type' => ['nullable', Rule::in(SyncTrigger::values())],
            'schedule_cron' => ['nullable', 'string', 'max:100'],
            'is_enabled' => ['nullable', 'boolean'],
            'source_path' => ['nullable', 'string', 'max:500'],
            'target_path' => ['nullable', 'string', 'max:500'],
            'entity_type' => ['nullable', 'string', 'max:100'],
            'conflict_strategy' => ['nullable', Rule::in(SyncConflictStrategy::values())],
            'batch_size' => ['nullable', 'integer', 'min:1', 'max:500'],
            'cursor_field' => ['nullable', 'string', 'max:100'],
            'field_mapping' => ['nullable', 'array'],
            'filters' => ['nullable', 'array'],
            'options' => ['nullable', 'array'],
        ];
    }
}

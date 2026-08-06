<?php

namespace App\Domains\Automation\Requests;

use App\Domains\Automation\Enums\AutomationActionType;
use App\Domains\Automation\Enums\AutomationConditionOperator;
use App\Domains\Automation\Enums\AutomationEventKey;
use App\Domains\Automation\Enums\AutomationTriggerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAutomationRuleRequest extends FormRequest
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
            'company_id' => ['nullable', 'string'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'trigger_type' => ['sometimes', 'string', Rule::in(AutomationTriggerType::values())],
            'event_key' => ['nullable', 'string', Rule::in(array_merge(AutomationEventKey::values(), ['schedule.run']))],
            'schedule_cron' => ['nullable', 'string', 'max:64'],
            'schedule_timezone' => ['nullable', 'string', 'max:64'],
            'delay_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'condition_logic' => ['nullable', 'string', Rule::in(['and', 'or'])],
            'is_enabled' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'conditions' => ['nullable', 'array'],
            'conditions.*.field' => ['required_with:conditions', 'string', 'max:100'],
            'conditions.*.operator' => ['required_with:conditions', 'string', Rule::in(AutomationConditionOperator::values())],
            'conditions.*.value' => ['nullable', 'string'],
            'conditions.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'actions' => ['sometimes', 'array', 'min:1'],
            'actions.*.action_type' => ['required_with:actions', 'string', Rule::in(AutomationActionType::values())],
            'actions.*.config' => ['nullable', 'array'],
            'actions.*.is_enabled' => ['nullable', 'boolean'],
            'actions.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

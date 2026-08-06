<?php

namespace App\Domains\Support\Requests;

use App\Domains\Support\Enums\SupportSlaEscalationLevel;
use App\Domains\Support\Enums\SupportSlaEscalationTrigger;
use App\Domains\Support\Enums\SupportTicketCategory;
use App\Domains\Support\Enums\SupportTicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportSlaPolicyRequest extends FormRequest
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
            'calendar_id' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:64'],
            'priority' => ['nullable', Rule::in(SupportTicketPriority::values())],
            'category' => ['nullable', Rule::in(SupportTicketCategory::values())],
            'response_target_minutes' => ['required', 'integer', 'min:1', 'max:525600'],
            'resolution_target_minutes' => ['required', 'integer', 'min:1', 'max:525600'],
            'at_risk_percent' => ['nullable', 'integer', 'min:1', 'max:99'],
            'business_hours_only' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
            'escalation_rules' => ['nullable', 'array', 'max:20'],
            'escalation_rules.*.level' => ['required_with:escalation_rules', Rule::in(SupportSlaEscalationLevel::values())],
            'escalation_rules.*.trigger' => ['required_with:escalation_rules', Rule::in(SupportSlaEscalationTrigger::values())],
            'escalation_rules.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'escalation_rules.*.notify_role' => ['nullable', 'string', 'max:64'],
            'escalation_rules.*.reassign_to_manager' => ['nullable', 'boolean'],
            'escalation_rules.*.is_active' => ['nullable', 'boolean'],
        ];
    }
}

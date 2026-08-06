<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Compliance\Enums\DataBreachSeverity;
use App\Domains\Compliance\Enums\DataBreachStatus;
use App\Domains\Compliance\Enums\DataBreachType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDataBreachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'breach_type' => ['sometimes', 'required', Rule::in(DataBreachType::values())],
            'status' => ['sometimes', 'required', Rule::in(DataBreachStatus::values())],
            'severity' => ['sometimes', 'required', Rule::in(DataBreachSeverity::values())],
            'discovered_at' => ['nullable', 'date'],
            'occurred_at' => ['nullable', 'date'],
            'affected_user_count' => ['nullable', 'integer', 'min:0'],
            'affected_users' => ['nullable', 'array'],
            'affected_users.*.email' => ['nullable', 'email', 'max:255'],
            'affected_users.*.name' => ['nullable', 'string', 'max:255'],
            'affected_users.*.user_id' => ['nullable', 'string'],
            'affected_data_categories' => ['nullable', 'array'],
            'affected_data_categories.*' => ['string', 'max:128'],
            'personal_data_involved' => ['nullable', 'boolean'],
            'special_category_data' => ['nullable', 'boolean'],
            'impact_analysis' => ['nullable', 'string', 'max:20000'],
            'containment_summary' => ['nullable', 'string', 'max:20000'],
            'recovery_summary' => ['nullable', 'string', 'max:20000'],
            'root_cause' => ['nullable', 'string', 'max:20000'],
            'lessons_learned' => ['nullable', 'string', 'max:20000'],
            'regulator_notification_required' => ['nullable', 'boolean'],
            'customer_notification_required' => ['nullable', 'boolean'],
            'regulator_reference' => ['nullable', 'string', 'max:128'],
            'assigned_to' => ['nullable', 'string'],
        ];
    }
}

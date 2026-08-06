<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Compliance\Enums\DataBreachSeverity;
use App\Domains\Compliance\Enums\DataBreachStatus;
use App\Domains\Compliance\Enums\DataBreachType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDataBreachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'breach_type' => ['required', Rule::in(DataBreachType::values())],
            'status' => ['nullable', Rule::in(DataBreachStatus::values())],
            'severity' => ['nullable', Rule::in(DataBreachSeverity::values())],
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
            'regulator_notification_required' => ['nullable', 'boolean'],
            'customer_notification_required' => ['nullable', 'boolean'],
            'assigned_to' => ['nullable', 'string'],
        ];
    }
}

<?php

namespace App\Domains\Analytics\Requests;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsDashboardKind;
use App\Domains\Analytics\Enums\AnalyticsDashboardStatus;
use App\Domains\Analytics\Enums\AnalyticsDashboardVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnalyticsDashboardRequest extends FormRequest
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
            'company' => ['nullable', 'string'],
            'company_id' => ['nullable'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'kind' => ['nullable', 'string', Rule::in(AnalyticsDashboardKind::values())],
            'category' => ['nullable', 'string', Rule::in(AnalyticsCategory::values())],
            'status' => ['nullable', 'string', Rule::in(AnalyticsDashboardStatus::values())],
            'visibility' => ['nullable', 'string', Rule::in(AnalyticsDashboardVisibility::values())],
            'layout' => ['nullable', 'array'],
            'filters' => ['nullable', 'array'],
            'filters.from' => ['nullable', 'date'],
            'filters.to' => ['nullable', 'date', 'after_or_equal:filters.from'],
            'settings' => ['nullable', 'array'],
            'is_default' => ['nullable', 'boolean'],
            'is_shared' => ['nullable', 'boolean'],
            'is_template' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

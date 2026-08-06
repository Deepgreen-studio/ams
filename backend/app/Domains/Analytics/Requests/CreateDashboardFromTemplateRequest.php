<?php

namespace App\Domains\Analytics\Requests;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsDashboardVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDashboardFromTemplateRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'company' => ['nullable', 'string'],
            'company_id' => ['nullable'],
            'category' => ['nullable', 'string', Rule::in(AnalyticsCategory::values())],
            'visibility' => ['nullable', 'string', Rule::in([
                AnalyticsDashboardVisibility::Personal->value,
                AnalyticsDashboardVisibility::Company->value,
                AnalyticsDashboardVisibility::Shared->value,
            ])],
            'filters' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
        ];
    }
}

<?php

namespace App\Domains\Analytics\Requests;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsDashboardKind;
use App\Domains\Analytics\Enums\AnalyticsDashboardStatus;
use App\Domains\Analytics\Enums\AnalyticsDashboardVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAnalyticsDashboardRequest extends FormRequest
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
            'owner_id' => ['nullable', 'integer'],
            'kind' => ['nullable', 'string', Rule::in(AnalyticsDashboardKind::values())],
            'category' => ['nullable', 'string', Rule::in(AnalyticsCategory::values())],
            'status' => ['nullable', 'string', Rule::in(AnalyticsDashboardStatus::values())],
            'visibility' => ['nullable', 'string', Rule::in(AnalyticsDashboardVisibility::values())],
            'is_system' => ['nullable'],
            'is_template' => ['nullable'],
            'mine' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', Rule::in(['name', 'category', 'status', 'sort_order', 'created_at', 'updated_at', 'visibility'])],
            'sort_dir' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $filters = $this->validated();

        if ($this->boolean('mine') && $this->user()) {
            $filters['owner_id'] = $this->user()->id;
            $filters['visibility'] = $filters['visibility'] ?? AnalyticsDashboardVisibility::Personal->value;
        }

        return $filters;
    }
}

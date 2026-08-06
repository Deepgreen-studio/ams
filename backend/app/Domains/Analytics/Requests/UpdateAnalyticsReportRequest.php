<?php

namespace App\Domains\Analytics\Requests;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsReportStatus;
use App\Domains\Analytics\Enums\AnalyticsReportType;
use App\Domains\Analytics\Enums\AnalyticsReportVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnalyticsReportRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', Rule::in(AnalyticsCategory::values())],
            'report_type' => ['nullable', 'string', Rule::in(AnalyticsReportType::values())],
            'status' => ['nullable', 'string', Rule::in(AnalyticsReportStatus::values())],
            'visibility' => ['nullable', 'string', Rule::in(AnalyticsReportVisibility::values())],
            'is_saved' => ['nullable', 'boolean'],
            'query_config' => ['nullable', 'array'],
            'columns' => ['nullable', 'array'],
            'columns.*.key' => ['required_with:columns', 'string', 'max:64'],
            'columns.*.label' => ['nullable', 'string', 'max:120'],
            'filters' => ['nullable', 'array'],
            'sorting' => ['nullable', 'array'],
            'sorting.field' => ['nullable', 'string', 'max:64'],
            'sorting.direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'grouping' => ['nullable', 'array'],
            'chart_config' => ['nullable', 'array'],
            'layout' => ['nullable', 'array'],
            'schedule_config' => ['nullable', 'array'],
            'schedule_config.enabled' => ['nullable', 'boolean'],
            'schedule_config.cron' => ['nullable', 'string', 'max:64'],
            'schedule_config.format' => ['nullable', 'string', 'max:32'],
            'schedule_config.timezone' => ['nullable', 'string', 'max:64'],
            'format_defaults' => ['nullable', 'array'],
        ];
    }
}

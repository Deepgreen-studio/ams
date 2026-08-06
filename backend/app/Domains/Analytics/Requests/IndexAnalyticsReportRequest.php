<?php

namespace App\Domains\Analytics\Requests;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsReportStatus;
use App\Domains\Analytics\Enums\AnalyticsReportType;
use App\Domains\Analytics\Enums\AnalyticsReportVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAnalyticsReportRequest extends FormRequest
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
            'category' => ['nullable', 'string', Rule::in(AnalyticsCategory::values())],
            'status' => ['nullable', 'string', Rule::in(AnalyticsReportStatus::values())],
            'report_type' => ['nullable', 'string', Rule::in(AnalyticsReportType::values())],
            'visibility' => ['nullable', 'string', Rule::in(AnalyticsReportVisibility::values())],
            'is_saved' => ['nullable', 'boolean'],
            'is_scheduled' => ['nullable', 'boolean'],
            'owner_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->validated();
    }
}

<?php

namespace App\Domains\Analytics\Requests;

use App\Domains\Analytics\Enums\AnalyticsReportFormat;
use App\Domains\Analytics\Enums\AnalyticsReportRunStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAnalyticsReportRunRequest extends FormRequest
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
            'status' => ['nullable', 'string', Rule::in(AnalyticsReportRunStatus::values())],
            'format' => ['nullable', 'string', Rule::in(AnalyticsReportFormat::values())],
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

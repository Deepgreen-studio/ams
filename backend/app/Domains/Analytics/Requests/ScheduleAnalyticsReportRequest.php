<?php

namespace App\Domains\Analytics\Requests;

use App\Domains\Analytics\Enums\AnalyticsReportFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleAnalyticsReportRequest extends FormRequest
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
            'enabled' => ['required', 'boolean'],
            'cron' => ['nullable', 'string', 'max:64'],
            'format' => ['nullable', 'string', Rule::in(AnalyticsReportFormat::values())],
            'timezone' => ['nullable', 'string', 'max:64'],
        ];
    }
}

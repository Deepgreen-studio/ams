<?php

namespace App\Domains\Analytics\Requests;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsWidgetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnalyticsWidgetRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'key' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'string', Rule::in(AnalyticsWidgetType::values())],
            'category' => ['nullable', 'string', Rule::in(AnalyticsCategory::values())],
            'data_source' => ['nullable', 'string', 'max:120'],
            'query_config' => ['nullable', 'array'],
            'visualization_config' => ['nullable', 'array'],
            'position_x' => ['nullable', 'integer', 'min:0', 'max:24'],
            'position_y' => ['nullable', 'integer', 'min:0', 'max:100'],
            'width' => ['nullable', 'integer', 'min:1', 'max:12'],
            'height' => ['nullable', 'integer', 'min:1', 'max:12'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'refresh_interval_seconds' => ['nullable', 'integer', 'min:30', 'max:86400'],
            'is_visible' => ['nullable', 'boolean'],
        ];
    }
}

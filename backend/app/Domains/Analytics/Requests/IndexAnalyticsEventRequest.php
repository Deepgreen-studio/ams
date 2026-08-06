<?php

namespace App\Domains\Analytics\Requests;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAnalyticsEventRequest extends FormRequest
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
            'event_name' => ['nullable', 'string', 'max:120'],
            'event_source' => ['nullable', 'string', 'max:120'],
            'application_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'sort_by' => ['nullable', 'string', Rule::in(['occurred_at', 'event_name', 'category', 'created_at'])],
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
        return $this->validated();
    }
}

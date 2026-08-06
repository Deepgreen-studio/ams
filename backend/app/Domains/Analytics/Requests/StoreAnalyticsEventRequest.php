<?php

namespace App\Domains\Analytics\Requests;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnalyticsEventRequest extends FormRequest
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
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'category' => ['required', 'string', Rule::in(AnalyticsCategory::values())],
            'event_name' => ['required', 'string', 'max:120'],
            'event_source' => ['nullable', 'string', 'max:120'],
            'subject_type' => ['nullable', 'string', 'max:120'],
            'subject_id' => ['nullable', 'string', 'max:64'],
            'properties' => ['nullable', 'array'],
            'metrics' => ['nullable', 'array'],
            'ip_address' => ['nullable', 'ip'],
            'user_agent' => ['nullable', 'string', 'max:2000'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}

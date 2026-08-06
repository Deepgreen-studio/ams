<?php

namespace App\Domains\Analytics\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportAnalyticsRequest extends FormRequest
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
            'company_id' => ['nullable', 'string'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'format' => ['nullable', Rule::in(['csv', 'excel', 'pdf'])],
            'report' => ['nullable', Rule::in([
                'overview',
                'dashboard',
                'notifications',
                'delivery',
                'automation',
                'workflows',
                'workflow',
                'ai',
            ])],
        ];
    }
}

<?php

namespace App\Domains\Support\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportSlaCalendarRequest extends FormRequest
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
            'company_id' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'business_hours' => ['required', 'array'],
            'business_hours.monday' => ['nullable', 'array', 'size:2'],
            'business_hours.tuesday' => ['nullable', 'array', 'size:2'],
            'business_hours.wednesday' => ['nullable', 'array', 'size:2'],
            'business_hours.thursday' => ['nullable', 'array', 'size:2'],
            'business_hours.friday' => ['nullable', 'array', 'size:2'],
            'business_hours.saturday' => ['nullable', 'array', 'size:2'],
            'business_hours.sunday' => ['nullable', 'array', 'size:2'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

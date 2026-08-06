<?php

namespace App\Domains\Support\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportSlaHolidayRequest extends FormRequest
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
            'calendar_id' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'holiday_date' => ['required', 'date'],
            'is_recurring' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

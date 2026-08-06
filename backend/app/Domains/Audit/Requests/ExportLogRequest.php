<?php

namespace App\Domains\Audit\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportLogRequest extends FormRequest
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
            'format' => ['nullable', 'string', 'in:csv,xlsx,pdf'],
            'search' => ['nullable', 'string', 'max:255'],
            'module' => ['nullable', 'string', 'max:64'],
            'action' => ['nullable', 'string', 'max:64'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}

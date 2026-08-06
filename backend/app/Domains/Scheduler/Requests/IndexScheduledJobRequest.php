<?php

namespace App\Domains\Scheduler\Requests;

use App\Domains\Scheduler\Enums\ScheduledJobType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexScheduledJobRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'job_type' => ['nullable', 'string', Rule::in(ScheduledJobType::values())],
            'handler_key' => ['nullable', 'string', 'max:100'],
            'is_enabled' => ['nullable'],
            'company_id' => ['nullable', 'string'],
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

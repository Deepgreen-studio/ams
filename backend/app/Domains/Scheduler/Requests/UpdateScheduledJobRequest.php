<?php

namespace App\Domains\Scheduler\Requests;

use App\Domains\Scheduler\Enums\ScheduledJobHandler;
use App\Domains\Scheduler\Enums\ScheduledJobType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduledJobRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'job_type' => ['sometimes', 'required', 'string', Rule::in(ScheduledJobType::values())],
            'handler_key' => ['sometimes', 'required', 'string', Rule::in(ScheduledJobHandler::values())],
            'schedule_cron' => ['nullable', 'string', 'max:64'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'run_at' => ['nullable', 'date'],
            'delay_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'queue_name' => ['nullable', 'string', 'max:64'],
            'is_enabled' => ['nullable', 'boolean'],
            'without_overlapping' => ['nullable', 'boolean'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
            'timeout_seconds' => ['nullable', 'integer', 'min:1', 'max:86400'],
            'payload' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

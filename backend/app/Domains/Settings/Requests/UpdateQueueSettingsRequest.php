<?php

namespace App\Domains\Settings\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQueueSettingsRequest extends FormRequest
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
            'default_connection' => ['sometimes', 'string', 'max:64'],
            'default_queue' => ['sometimes', 'string', 'max:64'],
            'retry_attempts' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'job_timeout' => ['sometimes', 'integer', 'min:10', 'max:3600'],
        ];
    }
}

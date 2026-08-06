<?php

namespace App\Domains\Queue\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DispatchSampleQueueJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel' => ['nullable', 'string', 'max:50'],
            'priority' => ['nullable', Rule::in(['high', 'normal', 'low'])],
            'delay_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'payload' => ['nullable', 'array'],
            'company_id' => ['nullable', 'integer'],
        ];
    }
}

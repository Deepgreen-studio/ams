<?php

namespace App\Domains\Workflows\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartWorkflowInstanceRequest extends FormRequest
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
            'subject_type' => ['nullable', 'string', 'max:120'],
            'subject_id' => ['nullable', 'string', 'max:64'],
            'subject_label' => ['nullable', 'string', 'max:255'],
            'context' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

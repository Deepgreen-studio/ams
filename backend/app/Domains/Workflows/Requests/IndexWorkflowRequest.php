<?php

namespace App\Domains\Workflows\Requests;

use App\Domains\Workflows\Enums\WorkflowDefinitionStatus;
use App\Domains\Workflows\Enums\WorkflowType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexWorkflowRequest extends FormRequest
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
            'type' => ['nullable', 'string', Rule::in(WorkflowType::values())],
            'status' => ['nullable', 'string', Rule::in(WorkflowDefinitionStatus::values())],
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

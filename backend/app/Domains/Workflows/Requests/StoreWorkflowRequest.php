<?php

namespace App\Domains\Workflows\Requests;

use App\Domains\Workflows\Enums\WorkflowDefinitionStatus;
use App\Domains\Workflows\Enums\WorkflowStepType;
use App\Domains\Workflows\Enums\WorkflowType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkflowRequest extends FormRequest
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
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', Rule::in(WorkflowType::values())],
            'status' => ['nullable', 'string', Rule::in(WorkflowDefinitionStatus::values())],
            'is_enabled' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'steps' => ['nullable', 'array', 'min:1'],
            'steps.*.name' => ['required_with:steps', 'string', 'max:255'],
            'steps.*.step_key' => ['nullable', 'string', 'max:64'],
            'steps.*.step_type' => ['required_with:steps', 'string', Rule::in(WorkflowStepType::values())],
            'steps.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'steps.*.position_x' => ['nullable', 'integer'],
            'steps.*.position_y' => ['nullable', 'integer'],
            'steps.*.config' => ['nullable', 'array'],
            'steps.*.next_step_keys' => ['nullable', 'array'],
            'steps.*.next_step_keys.*' => ['string', 'max:64'],
            'steps.*.on_approve_step_key' => ['nullable', 'string', 'max:64'],
            'steps.*.on_reject_step_key' => ['nullable', 'string', 'max:64'],
            'steps.*.is_required' => ['nullable', 'boolean'],
        ];
    }
}

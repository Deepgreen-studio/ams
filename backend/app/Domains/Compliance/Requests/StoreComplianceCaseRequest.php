<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Compliance\Enums\ComplianceCasePriority;
use App\Domains\Compliance\Enums\ComplianceCaseStatus;
use App\Domains\Compliance\Enums\ComplianceCaseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplianceCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'case_type' => ['required', Rule::in(ComplianceCaseType::values())],
            'priority' => ['nullable', Rule::in(ComplianceCasePriority::values())],
            'status' => ['nullable', Rule::in(ComplianceCaseStatus::values())],
            'assigned_to' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}

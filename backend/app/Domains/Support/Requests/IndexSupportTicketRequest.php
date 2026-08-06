<?php

namespace App\Domains\Support\Requests;

use App\Domains\Support\Enums\SupportTicketAssignmentType;
use App\Domains\Support\Enums\SupportTicketCategory;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketSource;
use App\Domains\Support\Enums\SupportTicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexSupportTicketRequest extends FormRequest
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
            'company' => ['nullable', 'string'],
            'company_id' => ['nullable', 'string'],
            'customer' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'string'],
            'application' => ['nullable', 'string'],
            'application_id' => ['nullable', 'string'],
            'department' => ['nullable', 'string'],
            'department_id' => ['nullable', 'string'],
            'team' => ['nullable', 'string'],
            'team_id' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(SupportTicketStatus::values())],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => [Rule::in(SupportTicketStatus::values())],
            'priority' => ['nullable', Rule::in(SupportTicketPriority::values())],
            'priorities' => ['nullable', 'array'],
            'priorities.*' => [Rule::in(SupportTicketPriority::values())],
            'category' => ['nullable', Rule::in(SupportTicketCategory::values())],
            'source' => ['nullable', Rule::in(SupportTicketSource::values())],
            'assignment_type' => ['nullable', Rule::in(SupportTicketAssignmentType::values())],
            'assigned_to' => ['nullable', 'string'],
            'assignee' => ['nullable', 'string'],
            'unassigned' => ['nullable', 'boolean'],
            'needs_assignment' => ['nullable', 'boolean'],
            'queue' => ['nullable', Rule::in(['open', 'unassigned', 'assignment', 'mine', 'critical', 'waiting', 'reopened'])],
            'per_column' => ['nullable', 'integer', 'min:5', 'max:50'],
            'trashed' => ['nullable', Rule::in(['with', 'only'])],
            'sort_by' => ['nullable', 'string', 'max:40'],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
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

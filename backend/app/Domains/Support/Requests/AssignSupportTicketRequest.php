<?php

namespace App\Domains\Support\Requests;

use App\Domains\Support\Enums\SupportTicketAssignmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignSupportTicketRequest extends FormRequest
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
            'type' => ['required', Rule::in(SupportTicketAssignmentType::values())],
            'assigned_to' => ['nullable', 'string'],
            'department_id' => ['nullable', 'string'],
            'team_id' => ['nullable', 'string'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

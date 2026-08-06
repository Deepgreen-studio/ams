<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Customers\Enums\CustomerTaskPriority;
use App\Domains\Customers\Enums\CustomerTaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['nullable', Rule::in(CustomerTaskStatus::values())],
            'priority' => ['nullable', Rule::in(CustomerTaskPriority::values())],
            'due_at' => ['nullable', 'date'],
            'remind_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'string'],
        ];
    }
}

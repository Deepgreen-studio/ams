<?php

namespace App\Domains\Companies\Requests;

use App\Domains\Companies\Enums\CompanyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'string'],
            'department_id' => ['nullable', 'string'],
            'manager_id' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::in(CompanyStatus::values())],
        ];
    }
}

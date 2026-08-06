<?php

namespace App\Domains\Roles\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:125',
                'regex:/^[A-Za-z0-9\-\_\s]+$/',
                Rule::unique('roles', 'name')->where(fn ($q) => $q->where('guard_name', $this->input('guard_name', 'web'))->whereNull('deleted_at')),
            ],
            'display_name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'guard_name' => ['nullable', 'string', 'max:50'],
            'is_system' => ['sometimes', 'boolean'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string'],
        ];
    }
}

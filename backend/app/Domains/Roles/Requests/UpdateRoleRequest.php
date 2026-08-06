<?php

namespace App\Domains\Roles\Requests;

use App\Domains\Roles\Repositories\RoleRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
        $role = app(RoleRepository::class)->findByIdentifier((string) $this->route('role'), withTrashed: true);
        $roleId = $role?->id;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:125',
                'regex:/^[A-Za-z0-9\-\_\s]+$/',
                Rule::unique('roles', 'name')
                    ->ignore($roleId)
                    ->where(fn ($q) => $q->where('guard_name', $role?->guard_name ?? 'web')->whereNull('deleted_at')),
            ],
            'display_name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string'],
        ];
    }
}

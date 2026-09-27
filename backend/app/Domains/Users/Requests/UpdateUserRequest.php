<?php

namespace App\Domains\Users\Requests;

use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Repositories\UserRepository;
use App\Shared\Http\NormalizesPhoneInput;
use App\Shared\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    use AuthorizesRoleAssignment;
    use NormalizesPhoneInput;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = app(UserRepository::class)->findByIdentifier((string) $this->route('user'), withTrashed: true);
        $userId = $user?->id;

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)->whereNull('deleted_at'),
            ],
            'phone' => [
                ...PhoneNumber::inputRules(),
                Rule::unique('users', 'phone')->ignore($userId)->whereNull('deleted_at'),
            ],
            'password' => ['prohibited'],
            'password_confirmation' => ['prohibited'],
            'gender' => ['prohibited'],
            'date_of_birth' => ['prohibited'],
            'timezone' => ['nullable', 'timezone:all'],
            'language' => ['nullable', 'string', 'max:16', 'regex:/^[a-z]{2,3}([_-][A-Za-z0-9]{2,8})*$/'],
            'status' => ['sometimes', 'required', Rule::in([
                UserStatus::Active->value,
                UserStatus::Inactive->value,
                UserStatus::Suspended->value,
            ])],
            'roles' => ['sometimes', 'array', 'min:1', 'max:1'],
            'roles.*' => ['required', 'string', 'max:255'],
            'company_id' => ['nullable', 'string', Rule::exists('companies', 'uuid')->whereNull('deleted_at')],
            'department_id' => ['nullable', 'string', Rule::exists('departments', 'uuid')->whereNull('deleted_at')],
            'team_id' => ['nullable', 'string', Rule::exists('teams', 'uuid')->whereNull('deleted_at')],
            'location_id' => ['nullable', 'string', Rule::exists('company_locations', 'uuid')->whereNull('deleted_at')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'roles' => 'role',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.prohibited' => 'Passwords are not set by an administrator. Send an invitation or password reset link instead.',
            'roles.min' => 'The role field is required.',
            'roles.max' => 'A user can have only one role.',
        ];
    }
}

<?php

namespace App\Domains\Users\Requests;

use App\Domains\Companies\Enums\CompanyStatus;
use App\Domains\Users\Enums\UserPermission;
use App\Shared\Http\NormalizesPhoneInput;
use App\Shared\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    use AuthorizesRoleAssignment;
    use NormalizesPhoneInput;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'phone' => [...PhoneNumber::inputRules(), Rule::unique('users', 'phone')->whereNull('deleted_at')],
            'password' => ['prohibited'],
            'password_confirmation' => ['prohibited'],
            'gender' => ['prohibited'],
            'date_of_birth' => ['prohibited'],
            'timezone' => ['nullable', 'timezone:all'],
            'language' => ['nullable', 'string', 'max:16', 'regex:/^[a-z]{2,3}([_-][A-Za-z0-9]{2,8})*$/'],
            'status' => ['prohibited'],
            'roles' => ['sometimes', 'array', 'min:1', 'max:1'],
            'roles.*' => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'string', Rule::exists('companies', 'uuid')->where('status', CompanyStatus::Active->value)->whereNull('deleted_at')],
            'department_id' => ['nullable', 'string', Rule::exists('departments', 'uuid')->where('status', CompanyStatus::Active->value)->whereNull('deleted_at')],
            'team_id' => ['nullable', 'string', Rule::exists('teams', 'uuid')->where('status', CompanyStatus::Active->value)->whereNull('deleted_at')],
            'location_id' => ['nullable', 'string', Rule::exists('company_locations', 'uuid')->where('status', CompanyStatus::Active->value)->whereNull('deleted_at')],
            'send_welcome_notification' => ['sometimes', 'boolean'],
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
            'password.prohibited' => 'Passwords are not set by an administrator. The user receives a one-time invitation link.',
            'roles.min' => 'The role field is required.',
            'roles.max' => 'A user can have only one role.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $actor = $this->user();

            if ($actor && $actor->can(UserPermission::ASSIGN_ROLES) && ! $this->filled('roles')) {
                $validator->errors()->add('roles', 'The role field is required.');
            }
        });
    }
}

<?php

namespace App\Domains\Users\Requests;

use App\Domains\Users\Enums\UserGender;
use App\Domains\Users\Enums\UserStatus;
use App\Shared\Http\NormalizesPhoneInput;
use App\Shared\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
            'password' => ['required', 'confirmed', Password::defaults()],
            'gender' => ['nullable', Rule::in(UserGender::values())],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'timezone' => ['nullable', 'timezone:all'],
            'language' => ['nullable', 'string', 'max:16'],
            'status' => ['nullable', Rule::in(UserStatus::values())],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['required', 'string', 'max:255'],
            'company_id' => ['nullable', 'string', Rule::exists('companies', 'uuid')->whereNull('deleted_at')],
            'department_id' => ['nullable', 'string', Rule::exists('departments', 'uuid')->whereNull('deleted_at')],
            'send_welcome_notification' => ['sometimes', 'boolean'],
        ];
    }
}

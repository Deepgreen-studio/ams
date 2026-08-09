<?php

namespace App\Domains\Users\Requests;

use App\Domains\Users\Enums\UserGender;
use App\Domains\Users\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->whereNull('deleted_at'), 'regex:/^\+?[0-9\s\-\(\)]{7,30}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'gender' => ['nullable', Rule::in(UserGender::values())],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'timezone' => ['nullable', 'timezone:all'],
            'language' => ['nullable', 'string', 'max:16'],
            'status' => ['nullable', Rule::in(UserStatus::values())],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['required', 'string', 'max:255'],
            'send_welcome_notification' => ['sometimes', 'boolean'],
        ];
    }
}

<?php

namespace App\Domains\Users\Requests;

use App\Domains\Users\Enums\UserGender;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Repositories\UserRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
                'nullable',
                'string',
                'max:30',
                'regex:/^\+?[0-9\s\-\(\)]{7,30}$/',
                Rule::unique('users', 'phone')->ignore($userId)->whereNull('deleted_at'),
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'gender' => ['nullable', Rule::in(UserGender::values())],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'timezone' => ['nullable', 'timezone:all'],
            'language' => ['nullable', 'string', 'max:16'],
            'status' => ['sometimes', 'required', Rule::in(UserStatus::values())],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['required', 'string', 'max:255'],
        ];
    }
}

<?php

namespace App\Domains\Users\Requests;

use App\Domains\Users\Enums\UserGender;
use App\Shared\Http\NormalizesPhoneInput;
use App\Shared\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    use NormalizesPhoneInput;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

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
            'gender' => ['nullable', Rule::in(UserGender::values())],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'timezone' => ['nullable', 'timezone:all'],
            'language' => ['nullable', 'string', 'max:16'],
        ];
    }
}

<?php

namespace App\Domains\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAffectedUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'affected_users' => ['required', 'array'],
            'affected_users.*.email' => ['nullable', 'email', 'max:255'],
            'affected_users.*.name' => ['nullable', 'string', 'max:255'],
            'affected_users.*.user_id' => ['nullable', 'string'],
            'affected_users.*.data_categories' => ['nullable', 'array'],
            'affected_data_categories' => ['nullable', 'array'],
            'affected_data_categories.*' => ['string', 'max:128'],
        ];
    }
}

<?php

namespace App\Domains\Applications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RollbackApplicationReleaseRequest extends FormRequest
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
            'reason' => ['nullable', 'string', 'max:2000'],
            'create_rollback_release' => ['nullable', 'boolean'],
            'rollback_release_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}

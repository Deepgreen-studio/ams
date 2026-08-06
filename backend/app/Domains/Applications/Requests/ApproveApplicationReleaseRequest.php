<?php

namespace App\Domains\Applications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveApplicationReleaseRequest extends FormRequest
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
            'approval_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

<?php

namespace App\Domains\Applications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeployApplicationReleaseRequest extends FormRequest
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
            'deployment_date' => ['nullable', 'date'],
            'failed' => ['nullable', 'boolean'],
        ];
    }
}

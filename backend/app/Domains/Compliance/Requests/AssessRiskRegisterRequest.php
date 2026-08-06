<?php

namespace App\Domains\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssessRiskRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'likelihood' => ['required', 'integer', 'min:1', 'max:5'],
            'impact' => ['required', 'integer', 'min:1', 'max:5'],
            'residual_likelihood' => ['nullable', 'integer', 'min:1', 'max:5'],
            'residual_impact' => ['nullable', 'integer', 'min:1', 'max:5'],
            'mitigation_plan' => ['nullable', 'string', 'max:20000'],
        ];
    }
}

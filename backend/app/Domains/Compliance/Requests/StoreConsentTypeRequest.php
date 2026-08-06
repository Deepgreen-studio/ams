<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Compliance\Enums\ConsentChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'max:64', 'alpha_dash:ascii'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'channel' => ['required', Rule::in(ConsentChannel::values())],
            'current_version' => ['nullable', 'string', 'max:32'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

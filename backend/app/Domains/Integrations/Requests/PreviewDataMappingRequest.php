<?php

namespace App\Domains\Integrations\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewDataMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source' => ['nullable', 'array'],
            'direction' => ['nullable', Rule::in(['inbound', 'outbound'])],
        ];
    }
}

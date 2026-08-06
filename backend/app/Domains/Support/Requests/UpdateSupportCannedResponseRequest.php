<?php

namespace App\Domains\Support\Requests;

use App\Domains\Support\Enums\CannedResponseVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportCannedResponseRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'shortcut' => ['nullable', 'string', 'max:64'],
            'body' => ['sometimes', 'required', 'string'],
            'body_format' => ['nullable', 'string', Rule::in(['html', 'markdown', 'plain'])],
            'visibility' => ['sometimes', 'required', 'string', Rule::in(CannedResponseVisibility::values())],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

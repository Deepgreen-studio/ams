<?php

namespace App\Domains\Support\Requests;

use App\Domains\Support\Enums\CannedResponseVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportCannedResponseRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'shortcut' => ['nullable', 'string', 'max:64'],
            'body' => ['required', 'string'],
            'body_format' => ['nullable', 'string', Rule::in(['html', 'markdown', 'plain'])],
            'visibility' => ['nullable', 'string', Rule::in(CannedResponseVisibility::values())],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

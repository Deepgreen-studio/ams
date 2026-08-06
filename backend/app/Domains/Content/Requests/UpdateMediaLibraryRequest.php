<?php

namespace App\Domains\Content\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaLibraryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'folder' => ['nullable', 'string'],
            'folder_id' => ['nullable', 'string'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

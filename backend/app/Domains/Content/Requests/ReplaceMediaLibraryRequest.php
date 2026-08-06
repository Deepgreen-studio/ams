<?php

namespace App\Domains\Content\Requests;

use App\Domains\Content\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;

class ReplaceMediaLibraryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mimes = implode(',', MediaType::allowedExtensions());

        return [
            'file' => ['required', 'file', 'max:102400', 'mimes:'.$mimes],
            'name' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'width' => ['nullable', 'integer', 'min:1'],
            'height' => ['nullable', 'integer', 'min:1'],
            'crop' => ['nullable', 'array'],
        ];
    }
}

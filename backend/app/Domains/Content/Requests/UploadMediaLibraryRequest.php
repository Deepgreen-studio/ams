<?php

namespace App\Domains\Content\Requests;

use App\Domains\Content\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadMediaLibraryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mimes = implode(',', MediaType::allowedExtensions());

        return [
            'files' => ['required_without:file', 'array', 'min:1'],
            'files.*' => ['file', 'max:102400', 'mimes:'.$mimes],
            'file' => ['required_without:files', 'file', 'max:102400', 'mimes:'.$mimes],
            'folder' => ['nullable', 'string'],
            'folder_id' => ['nullable', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'width' => ['nullable', 'integer', 'min:1'],
            'height' => ['nullable', 'integer', 'min:1'],
            'crop' => ['nullable', 'array'],
            'crop.x' => ['nullable', 'numeric'],
            'crop.y' => ['nullable', 'numeric'],
            'crop.width' => ['nullable', 'numeric'],
            'crop.height' => ['nullable', 'numeric'],
        ];
    }
}

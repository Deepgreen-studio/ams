<?php

namespace App\Domains\Settings\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
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
            'file' => ['required_without:files', 'file', 'max:102400'],
            'files' => ['required_without:file', 'array', 'min:1'],
            'files.*' => ['file', 'max:102400'],
            'folder_id' => ['nullable', 'string'],
        ];
    }
}

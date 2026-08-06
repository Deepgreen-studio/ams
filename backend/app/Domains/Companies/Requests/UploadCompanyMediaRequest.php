<?php

namespace App\Domains\Companies\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadCompanyMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,ico', 'max:2048'],
        ];
    }
}

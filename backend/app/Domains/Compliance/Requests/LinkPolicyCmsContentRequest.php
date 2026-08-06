<?php

namespace App\Domains\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkPolicyCmsContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_id' => ['required', 'string'],
        ];
    }
}

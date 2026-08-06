<?php

namespace App\Domains\Content\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkflowRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comments' => ['required', 'string', 'min:3', 'max:5000'],
        ];
    }
}

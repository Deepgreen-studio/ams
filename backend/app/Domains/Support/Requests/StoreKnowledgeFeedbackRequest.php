<?php

namespace App\Domains\Support\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKnowledgeFeedbackRequest extends FormRequest
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
            'is_helpful' => ['required', 'boolean'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

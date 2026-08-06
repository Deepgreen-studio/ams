<?php

namespace App\Domains\Ai\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiFeatureRequest extends FormRequest
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
            'text' => ['nullable', 'string', 'max:100000'],
            'content' => ['nullable', 'string', 'max:100000'],
            'message' => ['nullable', 'string', 'max:100000'],
            'question' => ['nullable', 'string', 'max:20000'],
            'subject' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:50000'],
            'labels' => ['nullable', 'array'],
            'labels.*' => ['string', 'max:120'],
            'teams' => ['nullable', 'array'],
            'teams.*' => ['string', 'max:120'],
            'target_locale' => ['nullable', 'string', 'max:16'],
            'source_locale' => ['nullable', 'string', 'max:16'],
            'query' => ['nullable', 'string', 'max:2000'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['string', 'max:20000'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'provider_id' => ['nullable', 'string'],
            'company_id' => ['nullable'],
            'model' => ['nullable', 'string', 'max:120'],
        ];
    }
}

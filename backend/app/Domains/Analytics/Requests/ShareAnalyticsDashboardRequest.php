<?php

namespace App\Domains\Analytics\Requests;

use App\Domains\Analytics\Enums\AnalyticsDashboardShareType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShareAnalyticsDashboardRequest extends FormRequest
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
            'share_type' => ['required', 'string', Rule::in(AnalyticsDashboardShareType::values())],
            'share_id' => ['nullable', 'integer'],
            'share_uuid' => ['nullable', 'string'],
            'identifier' => ['nullable', 'string'],
            'can_edit' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->filled('share_id') && ! $this->filled('share_uuid') && ! $this->filled('identifier')) {
                $validator->errors()->add('share_id', 'A share target is required.');
            }
        });
    }
}

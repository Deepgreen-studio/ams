<?php

namespace App\Domains\Integrations\Requests;

use App\Domains\Integrations\Enums\SyncMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RunSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => ['nullable', Rule::in(SyncMode::values())],
            'background' => ['nullable', 'boolean'],
        ];
    }
}

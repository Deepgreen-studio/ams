<?php

namespace App\Domains\Integrations\Requests;

use App\Domains\Integrations\Enums\HttpMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExecuteIntegrationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['headers', 'query', 'body'] as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $value = $this->input($field);
            if (is_string($value) && $value !== '') {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge([$field => $decoded]);
                }
            }
        }

        if ($this->has('apply_auth') && is_string($this->input('apply_auth'))) {
            $this->merge([
                'apply_auth' => filter_var($this->input('apply_auth'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }

        if ($this->has('as_download') && is_string($this->input('as_download'))) {
            $this->merge([
                'as_download' => filter_var($this->input('as_download'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'method' => ['required', Rule::in(HttpMethod::values())],
            'path' => ['required', 'string', 'max:2000'],
            'headers' => ['nullable', 'array'],
            'headers.*' => ['nullable', 'string', 'max:2000'],
            'query' => ['nullable', 'array'],
            'body' => ['nullable'],
            'apply_auth' => ['nullable', 'boolean'],
            'as_download' => ['nullable', 'boolean'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:300'],
            'retry_attempts' => ['nullable', 'integer', 'min:0', 'max:10'],
            'file' => ['nullable', 'file', 'max:10240'],
        ];
    }
}

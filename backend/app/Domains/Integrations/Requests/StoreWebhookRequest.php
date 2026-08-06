<?php

namespace App\Domains\Integrations\Requests;

use App\Domains\Integrations\Enums\WebhookDirection;
use App\Domains\Integrations\Enums\WebhookSignatureAlgorithm;
use App\Domains\Integrations\Enums\WebhookStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'string'],
            'integration_id' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash:ascii'],
            'description' => ['nullable', 'string', 'max:5000'],
            'direction' => ['required', Rule::in(WebhookDirection::values())],
            'status' => ['nullable', Rule::in(WebhookStatus::values())],
            'url' => ['nullable', 'url', 'max:500'],
            'secret' => ['nullable', 'string', 'max:500'],
            'signature_algorithm' => ['nullable', Rule::in(WebhookSignatureAlgorithm::values())],
            'signature_header' => ['nullable', 'string', 'max:100'],
            'subscribed_events' => ['nullable', 'array'],
            'subscribed_events.*' => ['string', 'max:150'],
            'headers' => ['nullable', 'array'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:300'],
            'retry_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
            'retry_delay_seconds' => ['nullable', 'integer', 'min:1', 'max:3600'],
            'verify_ssl' => ['nullable', 'boolean'],
        ];
    }
}

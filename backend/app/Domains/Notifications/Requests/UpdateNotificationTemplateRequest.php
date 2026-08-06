<?php

namespace App\Domains\Notifications\Requests;

use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationPriority;
use App\Domains\Notifications\Services\NotificationTemplateService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationTemplateRequest extends FormRequest
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
            'company_id' => ['nullable', 'string'],
            'channel' => ['sometimes', 'string', Rule::in(NotificationChannelEnum::values())],
            'locale' => ['sometimes', 'string', Rule::in(NotificationTemplateService::SUPPORTED_LOCALES)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['sometimes', 'required', 'string'],
            'available_variables' => ['nullable', 'array'],
            'available_variables.*' => ['string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'string', Rule::in(NotificationPriority::values())],
            'change_summary' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}

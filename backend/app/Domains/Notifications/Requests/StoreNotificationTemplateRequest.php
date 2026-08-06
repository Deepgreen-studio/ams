<?php

namespace App\Domains\Notifications\Requests;

use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationEventKey;
use App\Domains\Notifications\Enums\NotificationPriority;
use App\Domains\Notifications\Enums\NotificationTemplateStatus;
use App\Domains\Notifications\Services\NotificationTemplateService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationTemplateRequest extends FormRequest
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
            'event_key' => ['required', 'string', Rule::in(NotificationEventKey::values())],
            'channel' => ['required', 'string', Rule::in(NotificationChannelEnum::values())],
            'locale' => ['nullable', 'string', Rule::in(NotificationTemplateService::SUPPORTED_LOCALES)],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'available_variables' => ['nullable', 'array'],
            'available_variables.*' => ['string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'string', Rule::in(NotificationPriority::values())],
            'change_summary' => ['nullable', 'string', 'max:255'],
            'workflow_status' => ['nullable', 'string', Rule::in(NotificationTemplateStatus::values())],
        ];
    }
}

<?php

namespace App\Domains\Notifications\Requests;

use App\Domains\Notifications\Enums\NotificationEventKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncNotificationPreferencesRequest extends FormRequest
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
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*.event_key' => ['required', 'string', Rule::in(NotificationEventKey::values())],
            'preferences.*.email_enabled' => ['required', 'boolean'],
            'preferences.*.in_app_enabled' => ['required', 'boolean'],
            'preferences.*.sms_enabled' => ['nullable', 'boolean'],
            'preferences.*.push_enabled' => ['nullable', 'boolean'],
            'preferences.*.whatsapp_enabled' => ['nullable', 'boolean'],
            'preferences.*.slack_enabled' => ['nullable', 'boolean'],
            'preferences.*.teams_enabled' => ['nullable', 'boolean'],
            'preferences.*.webhook_enabled' => ['nullable', 'boolean'],
        ];
    }
}

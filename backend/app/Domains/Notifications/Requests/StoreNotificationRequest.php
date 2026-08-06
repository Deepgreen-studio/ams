<?php

namespace App\Domains\Notifications\Requests;

use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationPriority;
use App\Domains\Notifications\Enums\NotificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationRequest extends FormRequest
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
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'channel' => ['required', 'string', Rule::in(NotificationChannelEnum::values())],
            'template' => ['nullable', 'string', 'max:255'],
            'event_key' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(NotificationStatus::values())],
            'priority' => ['nullable', 'string', Rule::in(NotificationPriority::values())],
            'scheduled_at' => ['nullable', 'date'],
            'data' => ['nullable', 'array'],
        ];
    }
}

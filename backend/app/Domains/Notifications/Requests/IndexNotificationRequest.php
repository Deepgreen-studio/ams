<?php

namespace App\Domains\Notifications\Requests;

use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationPriority;
use App\Domains\Notifications\Enums\NotificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexNotificationRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'channel' => ['nullable', 'string', Rule::in(NotificationChannelEnum::values())],
            'status' => ['nullable', 'string', Rule::in(NotificationStatus::values())],
            'priority' => ['nullable', 'string', Rule::in(NotificationPriority::values())],
            'event_key' => ['nullable', 'string', 'max:100'],
            'unread' => ['nullable'],
            'read' => ['nullable'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->validated();
    }
}

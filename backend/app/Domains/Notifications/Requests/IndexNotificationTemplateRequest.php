<?php

namespace App\Domains\Notifications\Requests;

use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationTemplateStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexNotificationTemplateRequest extends FormRequest
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
            'event_key' => ['nullable', 'string', 'max:100'],
            'channel' => ['nullable', 'string', Rule::in(NotificationChannelEnum::values())],
            'locale' => ['nullable', 'string', 'max:16'],
            'workflow_status' => ['nullable', 'string', Rule::in(NotificationTemplateStatus::values())],
            'is_active' => ['nullable'],
            'company_id' => ['nullable', 'string'],
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

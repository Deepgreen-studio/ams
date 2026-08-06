<?php

namespace App\Domains\Compliance\Requests;

use App\Domains\Compliance\Enums\BreachNotificationChannel;
use App\Domains\Compliance\Enums\BreachNotificationStatus;
use App\Domains\Compliance\Enums\BreachNotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBreachNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notification_type' => ['required', Rule::in(BreachNotificationType::values())],
            'channel' => ['nullable', Rule::in(BreachNotificationChannel::values())],
            'recipient' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:20000'],
            'status' => ['nullable', Rule::in(BreachNotificationStatus::values())],
            'send_now' => ['nullable', 'boolean'],
            'regulator_reference' => ['nullable', 'string', 'max:128'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

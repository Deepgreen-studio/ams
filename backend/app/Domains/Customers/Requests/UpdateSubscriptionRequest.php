<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Customers\Enums\PaymentProvider;
use App\Domains\Customers\Enums\PaymentStatus;
use App\Domains\Customers\Enums\SubscriptionPlanType;
use App\Domains\Customers\Enums\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_application_id' => ['nullable', 'string'],
            'plan_type' => ['sometimes', 'required', Rule::in(SubscriptionPlanType::values())],
            'plan_name' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(SubscriptionStatus::values())],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'renews_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
            'features' => ['nullable'],
            'payment_status' => ['sometimes', 'required', Rule::in(PaymentStatus::values())],
            'payment_provider' => ['nullable', Rule::in(PaymentProvider::values())],
            'currency' => ['nullable', 'string', 'size:3'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'renewal_reminder_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

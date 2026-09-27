<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Applications\Enums\ApplicationPlatform;
use App\Domains\Customers\Enums\CustomerApplicationOwnershipType;
use App\Domains\Customers\Enums\CustomerApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerApplicationRequest extends FormRequest
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
            'customer_id' => ['required', 'string'],
            'application_id' => ['required', 'string'],
            'application_environment_id' => ['nullable', 'string'],
            'environment_id' => ['nullable', 'string'],
            'application_version_id' => ['nullable', 'string'],
            'version_id' => ['nullable', 'string'],
            'build_label' => ['nullable', 'string', 'max:64'],
            'application_release_id' => ['nullable', 'string'],
            'release_id' => ['nullable', 'string'],
            'integration_id' => ['nullable', 'string'],
            'support_sla_policy_id' => ['nullable', 'string'],
            'sla_policy_id' => ['nullable', 'string'],
            'subscription_id' => ['nullable', 'string'],
            'license_id' => ['nullable', 'string'],
            'owner_contact_id' => ['nullable', 'string'],
            'platform' => ['nullable', Rule::in(ApplicationPlatform::values())],
            'ownership_type' => ['nullable', Rule::in(CustomerApplicationOwnershipType::values())],
            'status' => ['nullable', Rule::in(CustomerApplicationStatus::values())],
            'activated_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:activated_at'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

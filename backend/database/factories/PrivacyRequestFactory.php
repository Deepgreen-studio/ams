<?php

namespace Database\Factories;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\PrivacyIdentityVerificationStatus;
use App\Domains\Compliance\Enums\PrivacyRequestStatus;
use App\Domains\Compliance\Enums\PrivacyRequestType;
use App\Domains\Compliance\Models\PrivacyRequest;
use App\Domains\Customers\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PrivacyRequest>
 */
class PrivacyRequestFactory extends Factory
{
    protected $model = PrivacyRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'request_number' => sprintf('PRV-%s-%05d', now()->format('Ymd'), fake()->unique()->numberBetween(1, 99999)),
            'request_type' => fake()->randomElement(PrivacyRequestType::values()),
            'requester_name' => fake()->name(),
            'requester_email' => fake()->safeEmail(),
            'requester_phone' => fake()->optional()->e164PhoneNumber(),
            'description' => fake()->paragraph(),
            'identity_verification_status' => PrivacyIdentityVerificationStatus::Pending->value,
            'status' => PrivacyRequestStatus::Submitted->value,
            'assigned_to' => null,
            'due_date' => now()->addDays(30)->toDateString(),
            'completed_at' => null,
            'decision' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => [
            'company_id' => $company->id,
        ]);
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => [
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'requester_name' => $customer->display_name,
            'requester_email' => $customer->email,
        ]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (): array => [
            'assigned_to' => $user->id,
            'status' => PrivacyRequestStatus::UnderReview->value,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (): array => [
            'identity_verification_status' => PrivacyIdentityVerificationStatus::Verified->value,
            'identity_verified_at' => now(),
            'status' => PrivacyRequestStatus::UnderReview->value,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\ConsentSource;
use App\Domains\Compliance\Enums\ConsentStatus;
use App\Domains\Compliance\Models\ConsentType;
use App\Domains\Compliance\Models\UserConsent;
use App\Domains\Customers\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserConsent>
 */
class UserConsentFactory extends Factory
{
    protected $model = UserConsent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'subject_email' => fake()->safeEmail(),
            'subject_name' => fake()->name(),
            'consent_version' => '1.0',
            'status' => ConsentStatus::Granted->value,
            'granted' => true,
            'consented_at' => now(),
            'withdrawn_at' => null,
            'ip_address' => fake()->ipv4(),
            'device' => fake()->randomElement(['Desktop Chrome', 'iPhone Safari', 'Android Chrome']),
            'user_agent' => fake()->userAgent(),
            'source' => fake()->randomElement(ConsentSource::values()),
            'notes' => null,
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => [
            'company_id' => $company->id,
        ]);
    }

    public function forType(ConsentType $type): static
    {
        return $this->state(function (array $attributes) use ($type): array {
            $state = [
                'consent_type_id' => $type->id,
                'consent_version' => $type->current_version,
            ];

            if ($type->company_id !== null) {
                $state['company_id'] = $type->company_id;
            } elseif (empty($attributes['company_id'])) {
                $state['company_id'] = $type->company_id;
            }

            return $state;
        });
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
            'subject_email' => $user->email,
            'subject_name' => $user->full_name,
        ]);
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => [
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'subject_email' => $customer->email,
            'subject_name' => $customer->display_name,
        ]);
    }

    public function withdrawn(): static
    {
        return $this->state(fn (): array => [
            'status' => ConsentStatus::Withdrawn->value,
            'granted' => false,
            'withdrawn_at' => now(),
        ]);
    }
}

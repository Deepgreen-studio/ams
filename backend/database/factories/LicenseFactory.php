<?php

namespace Database\Factories;

use App\Domains\Customers\Enums\LicenseStatus;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\License;
use App\Domains\Customers\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<License>
 */
class LicenseFactory extends Factory
{
    protected $model = License::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'customer_application_id' => null,
            'license_key' => License::generateLicenseKey(),
            'status' => LicenseStatus::Active->value,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
            'features' => ['dashboard', 'support'],
            'max_activations' => 5,
            'activation_count' => 0,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function forSubscription(Subscription $subscription): static
    {
        return $this->state(fn (): array => [
            'subscription_id' => $subscription->id,
            'customer_id' => $subscription->customer_id,
            'customer_application_id' => $subscription->customer_application_id,
            'features' => $subscription->features,
            'starts_at' => $subscription->starts_at,
            'expires_at' => $subscription->expires_at,
        ]);
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => [
            'customer_id' => $customer->id,
        ]);
    }
}

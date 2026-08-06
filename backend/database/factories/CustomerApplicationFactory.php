<?php

namespace Database\Factories;

use App\Domains\Customers\Enums\CustomerApplicationOwnershipType;
use App\Domains\Customers\Enums\CustomerApplicationStatus;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerApplication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomerApplication>
 */
class CustomerApplicationFactory extends Factory
{
    protected $model = CustomerApplication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'application_environment_id' => null,
            'integration_id' => null,
            'owner_contact_id' => null,
            'ownership_type' => CustomerApplicationOwnershipType::CustomerOwned->value,
            'status' => CustomerApplicationStatus::Active->value,
            'activated_at' => now(),
            'expires_at' => null,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => CustomerApplicationStatus::Pending->value,
            'activated_at' => null,
        ]);
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => [
            'customer_id' => $customer->id,
        ]);
    }
}

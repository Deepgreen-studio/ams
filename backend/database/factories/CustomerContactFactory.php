<?php

namespace Database\Factories;

use App\Domains\Customers\Enums\CustomerContactStatus;
use App\Domains\Customers\Enums\CustomerContactType;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerContact;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomerContact>
 */
class CustomerContactFactory extends Factory
{
    protected $model = CustomerContact::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'contact_type' => fake()->randomElement(CustomerContactType::values()),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->e164PhoneNumber(),
            'position' => fake()->optional()->jobTitle(),
            'department' => fake()->optional()->randomElement(['IT', 'Finance', 'Operations', 'Support', 'Sales']),
            'status' => CustomerContactStatus::Active->value,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (): array => [
            'contact_type' => CustomerContactType::Primary->value,
        ]);
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => [
            'customer_id' => $customer->id,
        ]);
    }
}

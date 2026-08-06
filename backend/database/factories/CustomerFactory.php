<?php

namespace Database\Factories;

use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Enums\CustomerStatus;
use App\Domains\Customers\Enums\CustomerType;
use App\Domains\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(CustomerType::values());
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'uuid' => (string) Str::uuid(),
            'customer_type' => $type,
            'first_name' => $type === CustomerType::Individual->value ? $firstName : fake()->optional()->firstName(),
            'last_name' => $type === CustomerType::Individual->value ? $lastName : fake()->optional()->lastName(),
            'company_name' => $type === CustomerType::Individual->value
                ? fake()->optional()->company()
                : fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->e164PhoneNumber(),
            'website' => fake()->optional()->url(),
            'industry' => fake()->optional()->randomElement(['Technology', 'Finance', 'Healthcare', 'Retail', 'Education']),
            'country' => fake()->optional()->countryCode(),
            'timezone' => 'UTC',
            'language' => 'en',
            'status' => CustomerStatus::Active->value,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function individual(): static
    {
        return $this->state(fn (): array => [
            'customer_type' => CustomerType::Individual->value,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company_name' => null,
        ]);
    }

    public function business(): static
    {
        return $this->state(fn (): array => [
            'customer_type' => CustomerType::Business->value,
            'company_name' => fake()->company(),
        ]);
    }

    public function enterprise(): static
    {
        return $this->state(fn (): array => [
            'customer_type' => CustomerType::Enterprise->value,
            'company_name' => fake()->company(),
        ]);
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => [
            'company_id' => $company->id,
        ]);
    }
}

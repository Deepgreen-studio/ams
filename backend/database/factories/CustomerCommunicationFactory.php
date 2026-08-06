<?php

namespace Database\Factories;

use App\Domains\Customers\Enums\CustomerCommunicationDirection;
use App\Domains\Customers\Enums\CustomerCommunicationStatus;
use App\Domains\Customers\Enums\CustomerCommunicationType;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerCommunication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomerCommunication>
 */
class CustomerCommunicationFactory extends Factory
{
    protected $model = CustomerCommunication::class;

    public function definition(): array
    {
        $type = fake()->randomElement(CustomerCommunicationType::values());

        return [
            'uuid' => (string) Str::uuid(),
            'type' => $type,
            'direction' => fake()->randomElement(CustomerCommunicationDirection::values()),
            'subject' => $type === CustomerCommunicationType::Call->value
                ? 'Phone call'
                : fake()->sentence(5),
            'body' => fake()->paragraph(),
            'status' => CustomerCommunicationStatus::Logged->value,
            'channel_reference' => fake()->optional()->uuid(),
            'participants' => [
                ['name' => fake()->name(), 'email' => fake()->safeEmail()],
            ],
            'duration_seconds' => $type === CustomerCommunicationType::Call->value
                ? fake()->numberBetween(60, 3600)
                : null,
            'occurred_at' => now()->subDays(fake()->numberBetween(0, 30)),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => ['customer_id' => $customer->id]);
    }
}

<?php

namespace Database\Factories;

use App\Domains\Customers\Enums\CustomerTaskPriority;
use App\Domains\Customers\Enums\CustomerTaskStatus;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerTask;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomerTask>
 */
class CustomerTaskFactory extends Factory
{
    protected $model = CustomerTask::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => CustomerTaskStatus::Open->value,
            'priority' => fake()->randomElement(CustomerTaskPriority::values()),
            'due_at' => now()->addDays(fake()->numberBetween(1, 21)),
            'remind_at' => now()->addDays(fake()->numberBetween(0, 14)),
            'completed_at' => null,
            'assigned_to' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => ['customer_id' => $customer->id]);
    }
}

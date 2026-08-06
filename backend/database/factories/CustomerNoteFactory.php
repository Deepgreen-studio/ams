<?php

namespace Database\Factories;

use App\Domains\Customers\Enums\CustomerNoteStatus;
use App\Domains\Customers\Enums\CustomerNoteType;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerNote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomerNote>
 */
class CustomerNoteFactory extends Factory
{
    protected $model = CustomerNote::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'note_type' => fake()->randomElement(CustomerNoteType::values()),
            'title' => fake()->optional()->sentence(4),
            'body' => fake()->paragraph(),
            'is_pinned' => false,
            'status' => CustomerNoteStatus::Active->value,
            'occurred_at' => now()->subDays(fake()->numberBetween(0, 14)),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => ['customer_id' => $customer->id]);
    }
}

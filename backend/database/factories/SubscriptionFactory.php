<?php

namespace Database\Factories;

use App\Domains\Customers\Enums\PaymentProvider;
use App\Domains\Customers\Enums\PaymentStatus;
use App\Domains\Customers\Enums\SubscriptionPlanType;
use App\Domains\Customers\Enums\SubscriptionStatus;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plan = fake()->randomElement(SubscriptionPlanType::values());
        $startsAt = now()->subDays(fake()->numberBetween(0, 30));

        return [
            'uuid' => (string) Str::uuid(),
            'customer_application_id' => null,
            'plan_type' => $plan,
            'plan_name' => Str::title(str_replace('_', ' ', $plan)).' Plan',
            'status' => $plan === SubscriptionPlanType::Trial->value
                ? SubscriptionStatus::Trialing->value
                : SubscriptionStatus::Active->value,
            'starts_at' => $startsAt,
            'expires_at' => $plan === SubscriptionPlanType::Lifetime->value ? null : $startsAt->copy()->addMonth(),
            'renews_at' => in_array($plan, [SubscriptionPlanType::Monthly->value, SubscriptionPlanType::Yearly->value], true)
                ? $startsAt->copy()->addMonth()
                : null,
            'trial_ends_at' => $plan === SubscriptionPlanType::Trial->value ? $startsAt->copy()->addDays(14) : null,
            'features' => ['dashboard', 'support'],
            'payment_status' => $plan === SubscriptionPlanType::Trial->value
                ? PaymentStatus::NotRequired->value
                : PaymentStatus::Paid->value,
            'payment_provider' => PaymentProvider::Manual->value,
            'currency' => 'USD',
            'amount' => $plan === SubscriptionPlanType::Trial->value ? 0 : fake()->randomFloat(2, 19, 499),
            'renewal_reminder_days' => 14,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => [
            'customer_id' => $customer->id,
        ]);
    }
}

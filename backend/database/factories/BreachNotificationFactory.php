<?php

namespace Database\Factories;

use App\Domains\Compliance\Enums\BreachNotificationChannel;
use App\Domains\Compliance\Enums\BreachNotificationStatus;
use App\Domains\Compliance\Enums\BreachNotificationType;
use App\Domains\Compliance\Models\BreachNotification;
use App\Domains\Compliance\Models\DataBreach;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BreachNotification>
 */
class BreachNotificationFactory extends Factory
{
    protected $model = BreachNotification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'notification_type' => BreachNotificationType::Internal->value,
            'channel' => BreachNotificationChannel::Email->value,
            'recipient' => fake()->safeEmail(),
            'subject' => 'Data breach notification',
            'message' => fake()->paragraph(),
            'status' => BreachNotificationStatus::Draft->value,
            'sent_at' => null,
            'acknowledged_at' => null,
            'sent_by' => null,
            'metadata' => null,
        ];
    }

    public function forBreach(DataBreach $breach): static
    {
        return $this->state(fn (): array => [
            'data_breach_id' => $breach->id,
        ]);
    }

    public function regulator(): static
    {
        return $this->state(fn (): array => [
            'notification_type' => BreachNotificationType::Regulator->value,
            'subject' => 'Regulator breach notification',
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => BreachNotificationStatus::Sent->value,
            'sent_at' => now(),
        ]);
    }
}

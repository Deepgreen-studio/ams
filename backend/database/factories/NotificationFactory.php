<?php

namespace Database\Factories;

use App\Domains\Notifications\Enums\NotificationChannel;
use App\Domains\Notifications\Enums\NotificationPriority;
use App\Domains\Notifications\Enums\NotificationStatus;
use App\Domains\Notifications\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'company_id' => null,
            'user_id' => User::factory(),
            'channel' => NotificationChannel::InApp->value,
            'template' => 'support.ticket_assigned',
            'event_key' => 'support.ticket_assigned',
            'title' => fake()->sentence(4),
            'message' => fake()->sentence(12),
            'status' => NotificationStatus::Sent->value,
            'priority' => NotificationPriority::Normal->value,
            'data' => [],
            'scheduled_at' => null,
            'sent_at' => now(),
            'read_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function unread(): static
    {
        return $this->state(fn (): array => ['read_at' => null]);
    }

    public function read(): static
    {
        return $this->state(fn (): array => ['read_at' => now()]);
    }
}

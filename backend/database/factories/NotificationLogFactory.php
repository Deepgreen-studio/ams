<?php

namespace Database\Factories;

use App\Domains\Notifications\Enums\NotificationChannel;
use App\Domains\Notifications\Enums\NotificationDeliveryStatus;
use App\Domains\Notifications\Enums\NotificationEventKey;
use App\Domains\Notifications\Models\NotificationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NotificationLog>
 */
class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'uuid' => (string) Str::uuid(),
            'notification_id' => null,
            'company_id' => null,
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'event_key' => NotificationEventKey::TicketCreated->value,
            'channel' => NotificationChannel::Email->value,
            'status' => NotificationDeliveryStatus::Sent->value,
            'recipient' => $user->email,
            'subject' => fake()->sentence(5),
            'body_preview' => fake()->sentence(10),
            'queued_at' => now(),
            'sent_at' => now(),
        ];
    }
}

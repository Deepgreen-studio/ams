<?php

namespace Database\Factories;

use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Models\NotificationChannel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NotificationChannel>
 */
class NotificationChannelFactory extends Factory
{
    protected $model = NotificationChannel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $channel = fake()->randomElement(NotificationChannelEnum::cases());

        return [
            'uuid' => (string) Str::uuid(),
            'key' => $channel->value,
            'name' => $channel->label(),
            'description' => $channel->description(),
            'is_enabled' => $channel->defaultEnabled(),
            'is_implemented' => $channel->isImplemented(),
            'is_system' => true,
            'sort_order' => 0,
            'config' => [],
        ];
    }
}

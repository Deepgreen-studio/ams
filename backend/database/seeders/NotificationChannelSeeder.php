<?php

namespace Database\Seeders;

use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Models\NotificationChannel;
use Illuminate\Database\Seeder;

class NotificationChannelSeeder extends Seeder
{
    public function run(): void
    {
        foreach (NotificationChannelEnum::cases() as $index => $channel) {
            NotificationChannel::query()->updateOrCreate(
                ['key' => $channel->value],
                [
                    'name' => $channel->label(),
                    'description' => $channel->description(),
                    'is_enabled' => $channel->defaultEnabled(),
                    'is_implemented' => $channel->isImplemented(),
                    'is_system' => true,
                    'sort_order' => ($index + 1) * 10,
                    'config' => [],
                ]
            );
        }
    }
}

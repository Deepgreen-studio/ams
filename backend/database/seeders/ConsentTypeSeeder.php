<?php

namespace Database\Seeders;

use App\Domains\Compliance\Enums\ConsentChannel;
use App\Domains\Compliance\Models\ConsentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ConsentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            [
                'channel' => ConsentChannel::Marketing,
                'description' => 'Consent to receive marketing communications and promotional content.',
                'sort_order' => 10,
            ],
            [
                'channel' => ConsentChannel::Analytics,
                'description' => 'Consent to collect analytics and usage telemetry.',
                'sort_order' => 20,
            ],
            [
                'channel' => ConsentChannel::PushNotification,
                'description' => 'Consent to receive push notifications on mobile and web devices.',
                'sort_order' => 30,
            ],
            [
                'channel' => ConsentChannel::Email,
                'description' => 'Consent to receive email communications.',
                'sort_order' => 40,
            ],
            [
                'channel' => ConsentChannel::Sms,
                'description' => 'Consent to receive SMS and text messages.',
                'sort_order' => 50,
            ],
            [
                'channel' => ConsentChannel::Cookie,
                'description' => 'Consent for non-essential cookies and similar tracking technologies.',
                'sort_order' => 60,
            ],
        ];

        foreach ($definitions as $definition) {
            /** @var ConsentChannel $channel */
            $channel = $definition['channel'];

            $existing = ConsentType::query()
                ->whereNull('company_id')
                ->where('code', $channel->value)
                ->first();

            if ($existing) {
                $existing->fill([
                    'name' => $channel->label(),
                    'description' => $definition['description'],
                    'channel' => $channel->value,
                    'is_active' => true,
                    'sort_order' => $definition['sort_order'],
                ])->save();

                continue;
            }

            ConsentType::query()->create([
                'uuid' => (string) Str::uuid(),
                'company_id' => null,
                'code' => $channel->value,
                'name' => $channel->label(),
                'description' => $definition['description'],
                'channel' => $channel->value,
                'current_version' => '1.0',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => $definition['sort_order'],
            ]);
        }
    }
}

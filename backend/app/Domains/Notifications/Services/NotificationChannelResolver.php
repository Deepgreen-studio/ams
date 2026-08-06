<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationEventKey;
use App\Domains\Notifications\Repositories\NotificationChannelRepository;
use App\Domains\Settings\Services\SystemSettingService;
use App\Models\User;
use App\Shared\Services\NotificationManager;

class NotificationChannelResolver
{
    public function __construct(
        private readonly NotificationManager $notificationManager,
        private readonly SystemSettingService $settingService,
        private readonly NotificationPreferenceService $preferenceService,
        private readonly NotificationChannelRepository $channelRepository,
    ) {}

    /**
     * @return list<NotificationChannelEnum>
     */
    public function resolveForUser(User $user, NotificationEventKey $eventKey): array
    {
        $stored = $this->channelRepository->allOrdered()->keyBy('key');

        $global = $this->notificationManager->channelMatrix([
            'email' => (bool) ($stored->get('email')?->is_enabled
                ?? $this->settingService->getValue('notifications', 'email_enabled', true)),
            'in_app' => (bool) ($stored->get('in_app')?->is_enabled
                ?? $this->settingService->getValue('notifications', 'in_app_enabled', true)),
            'push' => (bool) ($stored->get('push')?->is_enabled
                ?? $this->settingService->getValue('notifications', 'push_enabled', false)),
            'sms' => (bool) ($stored->get('sms')?->is_enabled ?? false),
            'whatsapp' => (bool) ($stored->get('whatsapp')?->is_enabled ?? false),
            'slack' => (bool) ($stored->get('slack')?->is_enabled ?? false),
            'teams' => (bool) ($stored->get('teams')?->is_enabled ?? false),
            'webhook' => (bool) ($stored->get('webhook')?->is_enabled ?? false),
        ]);

        $prefs = $this->preferenceService->forUserEvent($user, $eventKey);
        $channels = [];

        foreach (NotificationChannelEnum::cases() as $channel) {
            $globallyEnabled = (bool) ($global[$channel->value] ?? false);
            $preferenceEnabled = (bool) ($prefs[$channel->preferenceKey()] ?? false);
            $implemented = $channel->isImplemented() && ($stored->get($channel->value)?->is_implemented ?? $channel->isImplemented());

            if ($globallyEnabled && $preferenceEnabled && $implemented && $channel->laravelChannel() !== null) {
                $channels[] = $channel;
            }
        }

        return $channels;
    }
}

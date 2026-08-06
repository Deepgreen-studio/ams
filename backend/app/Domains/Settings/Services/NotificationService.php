<?php

namespace App\Domains\Settings\Services;

use App\Shared\Services\NotificationManager;

class NotificationService
{
    public function __construct(
        private readonly NotificationManager $notificationManager,
        private readonly SystemSettingService $settingService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        return [
            'email_enabled' => (bool) $this->settingService->getValue('notifications', 'email_enabled', true),
            'push_enabled' => (bool) $this->settingService->getValue('notifications', 'push_enabled', false),
            'in_app_enabled' => (bool) $this->settingService->getValue('notifications', 'in_app_enabled', true),
            'channels' => $this->notificationManager->channelMatrix([
                'email' => (bool) $this->settingService->getValue('notifications', 'email_enabled', true),
                'push' => (bool) $this->settingService->getValue('notifications', 'push_enabled', false),
                'in_app' => (bool) $this->settingService->getValue('notifications', 'in_app_enabled', true),
            ]),
        ];
    }
}

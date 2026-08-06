<?php

namespace App\Shared\Services;

use App\Domains\Settings\Services\SystemSettingService;

/**
 * Global notification channel matrix used by the Notifications domain.
 */
class NotificationManager
{
    public function __construct(
        private readonly ?SystemSettingService $settingService = null,
    ) {}

    /**
     * @param  array<string, mixed>  $channels
     * @return array<string, mixed>
     */
    public function channelMatrix(array $channels = []): array
    {
        $defaults = [
            'email' => true,
            'in_app' => true,
            'sms' => false,
            'push' => false,
            'whatsapp' => false,
            'slack' => false,
            'teams' => false,
            'webhook' => false,
        ];

        if ($this->settingService !== null && $channels === []) {
            $defaults['email'] = (bool) $this->settingService->getValue('notifications', 'email_enabled', true);
            $defaults['in_app'] = (bool) $this->settingService->getValue('notifications', 'in_app_enabled', true);
            $defaults['push'] = (bool) $this->settingService->getValue('notifications', 'push_enabled', false);
        }

        return array_merge($defaults, $channels);
    }

    public function isEmailEnabled(): bool
    {
        return (bool) ($this->channelMatrix()['email'] ?? true);
    }

    public function isInAppEnabled(): bool
    {
        return (bool) ($this->channelMatrix()['in_app'] ?? true);
    }
}

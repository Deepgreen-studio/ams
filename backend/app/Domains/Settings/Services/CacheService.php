<?php

namespace App\Domains\Settings\Services;

use App\Shared\Services\CacheManager;

class CacheService
{
    public function __construct(
        private readonly CacheManager $cacheManager,
        private readonly SystemSettingService $settingService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        return array_merge($this->cacheManager->status(), [
            'configured_driver' => $this->settingService->getValue('cache', 'driver'),
            'ttl_seconds' => (int) $this->settingService->getValue('cache', 'ttl_seconds', 3600),
        ]);
    }

    public function clear(): bool
    {
        return $this->cacheManager->flush();
    }
}

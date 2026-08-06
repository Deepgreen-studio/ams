<?php

namespace App\Domains\Settings\Services;

use App\Domains\Settings\Repositories\SystemSettingRepository;
use App\Shared\Services\CacheManager;

class ConfigurationService
{
    public function __construct(
        private readonly SystemSettingRepository $settingRepository,
        private readonly SystemSettingService $settingService,
        private readonly CacheManager $cacheManager
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function load(): array
    {
        return $this->settingRepository->cachedMap();
    }

    public function refreshCache(): void
    {
        $this->settingRepository->forgetCache();
        $this->settingRepository->cachedMap();
        $this->cacheManager->forget('ams.configuration');
    }

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        return $this->settingService->getValue($group, $key, $default);
    }
}

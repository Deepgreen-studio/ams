<?php

namespace App\Shared\Services\Http;

use App\Shared\Exceptions\ApiException;
use App\Shared\Services\CacheManager;

class RateLimitManager
{
    public function __construct(
        private readonly CacheManager $cacheManager
    ) {}

    public function assertAllowed(?string $key, ?int $perMinute): void
    {
        if ($key === null || $perMinute === null || $perMinute <= 0) {
            return;
        }

        $cacheKey = $this->cacheKey($key);
        $count = (int) $this->cacheManager->get($cacheKey, 0);

        if ($count >= $perMinute) {
            throw new ApiException('Integration rate limit exceeded. Try again shortly.', 429);
        }
    }

    public function hit(?string $key, ?int $perMinute): void
    {
        if ($key === null || $perMinute === null || $perMinute <= 0) {
            return;
        }

        $cacheKey = $this->cacheKey($key);
        $count = (int) $this->cacheManager->get($cacheKey, 0);
        $this->cacheManager->put($cacheKey, $count + 1, 60);
    }

    protected function cacheKey(string $key): string
    {
        return 'http_rate_limit:'.sha1($key);
    }
}

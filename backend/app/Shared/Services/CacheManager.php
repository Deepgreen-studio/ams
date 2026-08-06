<?php

namespace App\Shared\Services;

use Illuminate\Support\Facades\Cache;

class CacheManager
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    public function put(string $key, mixed $value, int $ttlSeconds = 3600): bool
    {
        return Cache::put($key, $value, $ttlSeconds);
    }

    public function remember(string $key, int $ttlSeconds, callable $callback): mixed
    {
        return Cache::remember($key, $ttlSeconds, $callback);
    }

    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    public function flush(): bool
    {
        return Cache::flush();
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        return [
            'driver' => config('cache.default'),
            'stores' => array_keys(config('cache.stores', [])),
        ];
    }
}

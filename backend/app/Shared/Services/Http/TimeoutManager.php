<?php

namespace App\Shared\Services\Http;

class TimeoutManager
{
    public function resolve(?int $timeout, int $fallback = 30): int
    {
        $value = $timeout ?? $fallback;

        return max(1, min($value, 300));
    }
}

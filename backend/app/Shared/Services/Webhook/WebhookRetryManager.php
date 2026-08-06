<?php

namespace App\Shared\Services\Webhook;

class WebhookRetryManager
{
    public function shouldRetry(int $attempts, int $maxAttempts, bool $successful): bool
    {
        return ! $successful && $attempts < $maxAttempts;
    }

    public function nextRetryAt(int $attempts, int $delaySeconds = 60): \DateTimeInterface
    {
        $multiplier = max(1, $attempts);
        $seconds = min(3600, max(1, $delaySeconds) * $multiplier);

        return now()->addSeconds($seconds);
    }
}

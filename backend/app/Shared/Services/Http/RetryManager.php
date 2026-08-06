<?php

namespace App\Shared\Services\Http;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class RetryManager
{
    /**
     * @param  Closure(PendingRequest): Response  $callback
     * @return array{response: Response|null, attempts: int, exception: \Throwable|null}
     */
    public function execute(PendingRequest $pending, Closure $callback, int $attempts = 1): array
    {
        $maxAttempts = max(1, min($attempts, 10));
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $callback($pending);

                if ($response->successful() || $attempt === $maxAttempts || ! $this->shouldRetryStatus($response->status())) {
                    return [
                        'response' => $response,
                        'attempts' => $attempt,
                        'exception' => null,
                    ];
                }
            } catch (ConnectionException $exception) {
                $lastException = $exception;
                if ($attempt === $maxAttempts) {
                    break;
                }
            }

            usleep($this->backoffMicros($attempt));
        }

        return [
            'response' => null,
            'attempts' => $maxAttempts,
            'exception' => $lastException,
        ];
    }

    protected function shouldRetryStatus(int $status): bool
    {
        return in_array($status, [408, 425, 429, 500, 502, 503, 504], true);
    }

    protected function backoffMicros(int $attempt): int
    {
        return (int) (min(2000, 200 * (2 ** ($attempt - 1))) * 1000);
    }
}

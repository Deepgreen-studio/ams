<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Integrations\Enums\WebhookLogStatus;
use App\Domains\Integrations\Events\WebhookDelivered;
use App\Domains\Integrations\Events\WebhookFailed;
use App\Domains\Integrations\Models\Webhook;
use App\Domains\Integrations\Models\WebhookLog;
use App\Domains\Integrations\Repositories\WebhookLogRepository;
use App\Domains\Integrations\Repositories\WebhookRepository;
use App\Shared\Services\Webhook\WebhookEngine;
use Illuminate\Support\Facades\Log;

class WebhookDeliveryService
{
    public function __construct(
        private readonly WebhookRepository $webhookRepository,
        private readonly WebhookLogRepository $webhookLogRepository,
        private readonly WebhookEngine $webhookEngine,
    ) {}

    public function processQueuedDelivery(int $webhookLogId): WebhookLog
    {
        /** @var WebhookLog|null $log */
        $log = WebhookLog::query()->with('webhook')->find($webhookLogId);
        if (! $log || ! $log->webhook) {
            Log::warning('Webhook delivery skipped; log or webhook missing.', ['log_id' => $webhookLogId]);
            abort(404, 'Webhook log not found.');
        }

        $webhook = $log->webhook;
        $attempts = (int) $log->attempts + 1;
        $maxAttempts = max(1, (int) $log->max_attempts);

        $this->webhookLogRepository->updateLog($log, [
            'status' => WebhookLogStatus::Processing->value,
            'attempts' => $attempts,
        ]);

        $payload = [];
        if (filled($log->request_body)) {
            $decoded = json_decode((string) $log->request_body, true);
            $payload = is_array($decoded) ? $decoded : ['raw' => $log->request_body];
        }

        try {
            $result = $this->webhookEngine->deliver($webhook->toEngineConfig(), $payload);
        } catch (\Throwable $exception) {
            return $this->markFailure($log, $webhook, $attempts, $maxAttempts, [
                'successful' => false,
                'status_code' => 0,
                'request_headers' => [],
                'request_body' => $log->request_body,
                'response_headers' => [],
                'response_body' => null,
                'duration_ms' => 0,
                'attempts' => $attempts,
                'error' => $exception->getMessage(),
                'url' => (string) $webhook->url,
                'method' => 'POST',
            ]);
        }

        if ($result->successful) {
            $updated = $this->webhookLogRepository->updateLog($log, [
                'status' => WebhookLogStatus::Success->value,
                'url' => $result->url,
                'method' => $result->method,
                'request_headers' => $result->requestHeaders,
                'request_body' => $result->requestBody,
                'response_status' => $result->statusCode,
                'response_headers' => $result->responseHeaders,
                'response_body' => $this->truncate($result->responseBody),
                'duration_ms' => $result->durationMs,
                'attempts' => $attempts,
                'error_message' => null,
                'next_retry_at' => null,
            ]);

            $this->webhookRepository->updateWebhook($webhook, [
                'last_success_at' => now(),
                'last_triggered_at' => now(),
            ]);

            event(new WebhookDelivered($webhook->fresh() ?? $webhook, $updated));

            return $updated;
        }

        return $this->markFailure($log, $webhook, $attempts, $maxAttempts, $result->toArray());
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function markFailure(
        WebhookLog $log,
        Webhook $webhook,
        int $attempts,
        int $maxAttempts,
        array $result,
    ): WebhookLog {
        $shouldRetry = $this->webhookEngine->retries()->shouldRetry($attempts, $maxAttempts, false);

        $updated = $this->webhookLogRepository->updateLog($log, [
            'status' => $shouldRetry ? WebhookLogStatus::Retrying->value : WebhookLogStatus::Failed->value,
            'url' => $result['url'] ?? $log->url,
            'method' => $result['method'] ?? 'POST',
            'request_headers' => $result['request_headers'] ?? $log->request_headers,
            'request_body' => $result['request_body'] ?? $log->request_body,
            'response_status' => ($result['status_code'] ?? 0) > 0 ? $result['status_code'] : null,
            'response_headers' => $result['response_headers'] ?? null,
            'response_body' => $this->truncate($result['response_body'] ?? null),
            'duration_ms' => $result['duration_ms'] ?? 0,
            'attempts' => $attempts,
            'error_message' => $result['error'] ?? 'Webhook delivery failed.',
            'next_retry_at' => $shouldRetry
                ? $this->webhookEngine->retries()->nextRetryAt($attempts, (int) $webhook->retry_delay_seconds)
                : null,
        ]);

        $this->webhookRepository->updateWebhook($webhook, [
            'last_failure_at' => now(),
            'last_triggered_at' => now(),
        ]);

        event(new WebhookFailed($webhook->fresh() ?? $webhook, $updated));

        return $updated;
    }

    protected function truncate(?string $value, int $limit = 50000): ?string
    {
        if ($value === null) {
            return null;
        }

        return strlen($value) <= $limit ? $value : substr($value, 0, $limit).'...[truncated]';
    }
}

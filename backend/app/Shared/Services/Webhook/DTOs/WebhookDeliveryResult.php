<?php

namespace App\Shared\Services\Webhook\DTOs;

final class WebhookDeliveryResult
{
    /**
     * @param  array<string, mixed>  $requestHeaders
     * @param  array<string, mixed>  $responseHeaders
     */
    public function __construct(
        public readonly bool $successful,
        public readonly int $statusCode,
        public readonly array $requestHeaders,
        public readonly ?string $requestBody,
        public readonly array $responseHeaders,
        public readonly ?string $responseBody,
        public readonly int $durationMs,
        public readonly int $attempts,
        public readonly ?string $error = null,
        public readonly string $url = '',
        public readonly string $method = 'POST',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'successful' => $this->successful,
            'status_code' => $this->statusCode,
            'request_headers' => $this->requestHeaders,
            'request_body' => $this->requestBody,
            'response_headers' => $this->responseHeaders,
            'response_body' => $this->responseBody,
            'duration_ms' => $this->durationMs,
            'attempts' => $this->attempts,
            'error' => $this->error,
            'url' => $this->url,
            'method' => $this->method,
        ];
    }
}

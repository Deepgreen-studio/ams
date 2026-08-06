<?php

namespace App\Shared\Services\Webhook;

use App\Shared\Exceptions\ApiException;
use App\Shared\Services\Http\ApiClientService;
use App\Shared\Services\Http\DTOs\HttpRequestDto;
use App\Shared\Services\Webhook\DTOs\WebhookDeliveryResult;

/**
 * Sends outgoing webhook HTTP deliveries exclusively via ApiClientService.
 */
class WebhookDispatcher
{
    public function __construct(
        private readonly ApiClientService $apiClientService,
        private readonly SignatureValidator $signatureValidator,
    ) {}

    /**
     * @param  array<string, mixed>  $webhook
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $extraHeaders
     */
    public function deliver(array $webhook, array $payload, array $extraHeaders = []): WebhookDeliveryResult
    {
        $url = (string) ($webhook['url'] ?? '');
        if ($url === '') {
            throw new ApiException('Outgoing webhook URL is required.', 422);
        }

        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $headers = array_merge(
            ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'User-Agent' => 'AMS-WebhookEngine/1.0'],
            (array) ($webhook['headers'] ?? []),
            $extraHeaders,
        );

        $secret = (string) ($webhook['secret'] ?? '');
        $algorithm = (string) ($webhook['signature_algorithm'] ?? 'hmac_sha256');
        $signatureHeader = (string) ($webhook['signature_header'] ?? 'X-AMS-Signature');

        if ($secret !== '' && $algorithm !== 'none') {
            $headers[$signatureHeader] = $this->signatureValidator->generate($body, $secret, $algorithm);
        }

        $response = $this->apiClientService->send(new HttpRequestDto(
            method: 'POST',
            url: $url,
            headers: $headers,
            body: $body,
            timeout: isset($webhook['timeout']) ? (int) $webhook['timeout'] : 30,
            retryAttempts: 1,
        ));

        return new WebhookDeliveryResult(
            successful: $response->successful,
            statusCode: $response->statusCode,
            requestHeaders: $this->maskHeaders($headers),
            requestBody: $body,
            responseHeaders: $response->headers,
            responseBody: $response->rawBody ?? (is_string($response->body) ? $response->body : json_encode($response->body)),
            durationMs: $response->durationMs,
            attempts: $response->attempts,
            error: $response->error,
            url: $url,
            method: 'POST',
        );
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    protected function maskHeaders(array $headers): array
    {
        $masked = [];
        foreach ($headers as $key => $value) {
            $lower = strtolower((string) $key);
            if (str_contains($lower, 'signature') || str_contains($lower, 'authorization') || str_contains($lower, 'secret')) {
                $masked[$key] = '***MASKED***';
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }
}

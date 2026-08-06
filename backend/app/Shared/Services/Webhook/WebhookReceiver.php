<?php

namespace App\Shared\Services\Webhook;

use App\Shared\Exceptions\ApiException;
use Illuminate\Http\Request;

/**
 * Handles incoming webhook reception and secret/signature validation.
 */
class WebhookReceiver
{
    public function __construct(
        private readonly SignatureValidator $signatureValidator,
    ) {}

    /**
     * @param  array<string, mixed>  $webhook
     * @return array{payload: array<string, mixed>, raw_body: string, headers: array<string, string>}
     */
    public function receive(array $webhook, Request $request): array
    {
        if (($webhook['direction'] ?? null) !== 'incoming') {
            throw new ApiException('Webhook is not configured for incoming requests.', 422);
        }

        if (($webhook['status'] ?? null) !== 'active') {
            throw new ApiException('Webhook is not active.', 422);
        }

        $rawBody = $request->getContent();
        $secret = (string) ($webhook['secret'] ?? '');
        $algorithm = (string) ($webhook['signature_algorithm'] ?? 'hmac_sha256');
        $signatureHeader = (string) ($webhook['signature_header'] ?? 'X-AMS-Signature');
        $provided = $request->header($signatureHeader) ?: $request->header('X-Hub-Signature-256');

        if ($secret !== '') {
            $this->signatureValidator->assertValid($rawBody, $secret, $provided, $algorithm);
        }

        $payload = $request->all();
        if ($payload === [] && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            $payload = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : ['raw' => $rawBody];
        }

        return [
            'payload' => $payload,
            'raw_body' => $rawBody,
            'headers' => $this->extractHeaders($request),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function extractHeaders(Request $request): array
    {
        $headers = [];
        foreach ($request->headers->all() as $key => $values) {
            $headers[(string) $key] = is_array($values) ? implode(', ', $values) : (string) $values;
        }

        return $headers;
    }
}

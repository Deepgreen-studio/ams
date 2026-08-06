<?php

namespace App\Shared\Services\Webhook;

/**
 * Enterprise webhook engine facade.
 *
 * Future modules MUST use this class for webhook delivery/reception.
 * Never implement one-off webhook HTTP calls in business modules.
 */
class WebhookEngine
{
    public function __construct(
        private readonly WebhookDispatcher $dispatcher,
        private readonly WebhookReceiver $receiver,
        private readonly SignatureValidator $signatureValidator,
        private readonly WebhookRetryManager $retryManager,
    ) {}

    public function dispatcher(): WebhookDispatcher
    {
        return $this->dispatcher;
    }

    public function receiver(): WebhookReceiver
    {
        return $this->receiver;
    }

    public function signatures(): SignatureValidator
    {
        return $this->signatureValidator;
    }

    public function retries(): WebhookRetryManager
    {
        return $this->retryManager;
    }

    /**
     * @param  array<string, mixed>  $webhook
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $extraHeaders
     */
    public function deliver(array $webhook, array $payload, array $extraHeaders = []): DTOs\WebhookDeliveryResult
    {
        return $this->dispatcher->deliver($webhook, $payload, $extraHeaders);
    }

    /**
     * @param  array<string, mixed>  $webhook
     * @return array{payload: array<string, mixed>, raw_body: string, headers: array<string, string>}
     */
    public function receive(array $webhook, \Illuminate\Http\Request $request): array
    {
        return $this->receiver->receive($webhook, $request);
    }
}

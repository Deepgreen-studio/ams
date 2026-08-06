<?php

namespace App\Domains\Customers\DTOs;

final class BillingGatewayResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?string $externalSubscriptionId = null,
        public readonly ?string $externalCustomerId = null,
        public readonly ?string $paymentStatus = null,
        public readonly array $payload = [],
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function success(
        string $message,
        ?string $externalSubscriptionId = null,
        ?string $externalCustomerId = null,
        ?string $paymentStatus = null,
        array $payload = [],
    ): self {
        return new self(true, $message, $externalSubscriptionId, $externalCustomerId, $paymentStatus, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function failure(string $message, array $payload = []): self
    {
        return new self(false, $message, payload: $payload);
    }
}

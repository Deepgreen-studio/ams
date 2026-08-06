<?php

namespace App\Domains\Customers\Contracts;

use App\Domains\Customers\DTOs\BillingGatewayResult;
use App\Domains\Customers\Models\Subscription;

/**
 * Future Stripe (and other providers) implement this contract.
 * Current runtime uses ManualBillingGateway.
 */
interface SubscriptionBillingGatewayInterface
{
    public function provider(): string;

    public function createSubscription(Subscription $subscription, array $options = []): BillingGatewayResult;

    public function cancelSubscription(Subscription $subscription, array $options = []): BillingGatewayResult;

    public function syncPaymentStatus(Subscription $subscription): BillingGatewayResult;
}

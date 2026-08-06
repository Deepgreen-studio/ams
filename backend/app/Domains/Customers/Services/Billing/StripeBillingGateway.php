<?php

namespace App\Domains\Customers\Services\Billing;

use App\Domains\Customers\Contracts\SubscriptionBillingGatewayInterface;
use App\Domains\Customers\DTOs\BillingGatewayResult;
use App\Domains\Customers\Enums\PaymentProvider;
use App\Domains\Customers\Models\Subscription;
use RuntimeException;

/**
 * Placeholder Stripe gateway for future Cashier/Stripe API integration.
 * Bound only when billing.default_provider=stripe.
 */
class StripeBillingGateway implements SubscriptionBillingGatewayInterface
{
    public function provider(): string
    {
        return PaymentProvider::Stripe->value;
    }

    public function createSubscription(Subscription $subscription, array $options = []): BillingGatewayResult
    {
        throw new RuntimeException('Stripe billing is not implemented yet. Configure billing.default_provider=manual.');
    }

    public function cancelSubscription(Subscription $subscription, array $options = []): BillingGatewayResult
    {
        throw new RuntimeException('Stripe billing is not implemented yet. Configure billing.default_provider=manual.');
    }

    public function syncPaymentStatus(Subscription $subscription): BillingGatewayResult
    {
        throw new RuntimeException('Stripe billing is not implemented yet. Configure billing.default_provider=manual.');
    }
}

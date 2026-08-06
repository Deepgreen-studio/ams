<?php

namespace App\Domains\Customers\Services\Billing;

use App\Domains\Customers\Contracts\SubscriptionBillingGatewayInterface;
use App\Domains\Customers\DTOs\BillingGatewayResult;
use App\Domains\Customers\Enums\PaymentProvider;
use App\Domains\Customers\Enums\PaymentStatus;
use App\Domains\Customers\Models\Subscription;

/**
 * Default billing gateway until Stripe (or another provider) is wired.
 */
class ManualBillingGateway implements SubscriptionBillingGatewayInterface
{
    public function provider(): string
    {
        return PaymentProvider::Manual->value;
    }

    public function createSubscription(Subscription $subscription, array $options = []): BillingGatewayResult
    {
        return BillingGatewayResult::success(
            'Manual billing subscription prepared. Stripe gateway can replace this later.',
            paymentStatus: $subscription->payment_status?->value ?? PaymentStatus::NotRequired->value,
            payload: [
                'provider' => $this->provider(),
                'subscription_uuid' => $subscription->uuid,
            ],
        );
    }

    public function cancelSubscription(Subscription $subscription, array $options = []): BillingGatewayResult
    {
        return BillingGatewayResult::success(
            'Manual billing cancellation recorded.',
            paymentStatus: $subscription->payment_status?->value,
            payload: [
                'provider' => $this->provider(),
                'subscription_uuid' => $subscription->uuid,
            ],
        );
    }

    public function syncPaymentStatus(Subscription $subscription): BillingGatewayResult
    {
        return BillingGatewayResult::success(
            'Payment status sync is a no-op for manual billing.',
            paymentStatus: $subscription->payment_status?->value,
            payload: [
                'provider' => $this->provider(),
                'subscription_uuid' => $subscription->uuid,
            ],
        );
    }
}

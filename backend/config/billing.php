<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Billing Provider
    |--------------------------------------------------------------------------
    |
    | Supported: "manual", "stripe"
    | Stripe remains architecture-ready via SubscriptionBillingGatewayInterface.
    |
    */
    'default_provider' => env('BILLING_PROVIDER', 'manual'),

    'currency' => env('BILLING_CURRENCY', 'USD'),

    'renewal_reminder_days' => (int) env('BILLING_RENEWAL_REMINDER_DAYS', 14),

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'public' => env('STRIPE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];

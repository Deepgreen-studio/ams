<?php

namespace App\Domains\Customers\Enums;

enum SubscriptionPlanType: string
{
    case Trial = 'trial';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case Lifetime = 'lifetime';
    case Enterprise = 'enterprise';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Trial',
            self::Monthly => 'Monthly',
            self::Yearly => 'Yearly',
            self::Lifetime => 'Lifetime',
            self::Enterprise => 'Enterprise',
        };
    }

    public function defaultPaymentStatus(): PaymentStatus
    {
        return match ($this) {
            self::Trial, self::Lifetime => PaymentStatus::NotRequired,
            default => PaymentStatus::Pending,
        };
    }
}

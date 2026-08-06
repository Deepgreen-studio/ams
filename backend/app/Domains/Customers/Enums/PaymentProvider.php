<?php

namespace App\Domains\Customers\Enums;

enum PaymentProvider: string
{
    case Manual = 'manual';
    case Stripe = 'stripe';

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
            self::Manual => 'Manual',
            self::Stripe => 'Stripe',
        };
    }
}

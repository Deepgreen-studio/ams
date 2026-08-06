<?php

namespace App\Domains\Customers\Enums;

enum CustomerContactType: string
{
    case Primary = 'primary';
    case Technical = 'technical';
    case Billing = 'billing';
    case Support = 'support';
    case Emergency = 'emergency';

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
            self::Primary => 'Primary',
            self::Technical => 'Technical',
            self::Billing => 'Billing',
            self::Support => 'Support',
            self::Emergency => 'Emergency',
        };
    }

    public function isPrimary(): bool
    {
        return $this === self::Primary;
    }
}

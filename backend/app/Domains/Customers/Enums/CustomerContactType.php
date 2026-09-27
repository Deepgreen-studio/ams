<?php

namespace App\Domains\Customers\Enums;

enum CustomerContactType: string
{
    case Primary = 'primary';
    case Technical = 'technical';
    case Support = 'support';
    case Billing = 'billing';
    case Security = 'security';
    case Compliance = 'compliance';
    case Business = 'business';
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
            self::Support => 'Support',
            self::Billing => 'Billing',
            self::Security => 'Security',
            self::Compliance => 'Compliance / Privacy',
            self::Business => 'Business',
            self::Emergency => 'Emergency',
        };
    }

    public function isPrimary(): bool
    {
        return $this === self::Primary;
    }
}

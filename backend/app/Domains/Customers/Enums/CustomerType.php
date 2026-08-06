<?php

namespace App\Domains\Customers\Enums;

enum CustomerType: string
{
    case Individual = 'individual';
    case Business = 'business';
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
            self::Individual => 'Individual',
            self::Business => 'Business',
            self::Enterprise => 'Enterprise',
        };
    }

    public function requiresCompanyName(): bool
    {
        return $this === self::Business || $this === self::Enterprise;
    }

    public function requiresPersonName(): bool
    {
        return $this === self::Individual;
    }
}

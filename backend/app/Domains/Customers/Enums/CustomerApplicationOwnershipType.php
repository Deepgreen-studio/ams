<?php

namespace App\Domains\Customers\Enums;

enum CustomerApplicationOwnershipType: string
{
    case CustomerOwned = 'customer_owned';
    case PlatformManaged = 'platform_managed';
    case Shared = 'shared';

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
            self::CustomerOwned => 'Customer Owned',
            self::PlatformManaged => 'Platform Managed',
            self::Shared => 'Shared',
        };
    }
}

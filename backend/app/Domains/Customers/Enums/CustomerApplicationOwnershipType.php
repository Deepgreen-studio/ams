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

    /**
     * Operational responsibility for the entitlement. The company remains the
     * owner of the application record.
     */
    public function description(): string
    {
        return match ($this) {
            self::CustomerOwned => 'The customer operates this entitlement. The company still owns the application.',
            self::PlatformManaged => 'The company operates the application for the customer.',
            self::Shared => 'The company and the customer share operational responsibility.',
        };
    }
}

<?php

namespace App\Domains\Analytics\Enums;

enum ExecutiveDashboardType: string
{
    case Ceo = 'ceo';
    case Admin = 'admin';
    case Operations = 'operations';
    case Compliance = 'compliance';
    case Support = 'support';
    case Customer = 'customer';

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
            self::Ceo => 'CEO Dashboard',
            self::Admin => 'Admin Dashboard',
            self::Operations => 'Operations Dashboard',
            self::Compliance => 'Compliance Dashboard',
            self::Support => 'Support Dashboard',
            self::Customer => 'Customer Dashboard',
        };
    }
}

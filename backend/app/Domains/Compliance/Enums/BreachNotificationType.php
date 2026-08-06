<?php

namespace App\Domains\Compliance\Enums;

enum BreachNotificationType: string
{
    case Regulator = 'regulator';
    case Customer = 'customer';
    case Internal = 'internal';
    case AffectedUser = 'affected_user';

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
            self::Regulator => 'Regulator',
            self::Customer => 'Customer',
            self::Internal => 'Internal',
            self::AffectedUser => 'Affected User',
        };
    }
}

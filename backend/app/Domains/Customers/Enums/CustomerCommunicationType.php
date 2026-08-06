<?php

namespace App\Domains\Customers\Enums;

enum CustomerCommunicationType: string
{
    case Email = 'email';
    case Call = 'call';
    case Meeting = 'meeting';

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
            self::Email => 'Email',
            self::Call => 'Call',
            self::Meeting => 'Meeting',
        };
    }
}

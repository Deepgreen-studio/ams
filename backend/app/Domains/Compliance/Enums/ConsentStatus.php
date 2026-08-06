<?php

namespace App\Domains\Compliance\Enums;

enum ConsentStatus: string
{
    case Pending = 'pending';
    case Granted = 'granted';
    case Withdrawn = 'withdrawn';
    case Expired = 'expired';

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
            self::Pending => 'Pending',
            self::Granted => 'Granted',
            self::Withdrawn => 'Withdrawn',
            self::Expired => 'Expired',
        };
    }
}

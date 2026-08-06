<?php

namespace App\Domains\Applications\Enums;

enum ApplicationCrashStatus: string
{
    case Open = 'open';
    case Investigating = 'investigating';
    case Resolved = 'resolved';
    case Ignored = 'ignored';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Investigating => 'Investigating',
            self::Resolved => 'Resolved',
            self::Ignored => 'Ignored',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

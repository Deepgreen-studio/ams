<?php

namespace App\Domains\Applications\Enums;

enum ApplicationCrashType: string
{
    case Crash = 'crash';
    case Anr = 'anr';
    case ApiError = 'api_error';

    public function label(): string
    {
        return match ($this) {
            self::Crash => 'Crash',
            self::Anr => 'ANR',
            self::ApiError => 'API Error',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

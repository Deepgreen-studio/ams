<?php

namespace App\Domains\Applications\Enums;

enum ApplicationCrashSeverity: string
{
    case Fatal = 'fatal';
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';

    public function label(): string
    {
        return match ($this) {
            self::Fatal => 'Fatal',
            self::Error => 'Error',
            self::Warning => 'Warning',
            self::Info => 'Info',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

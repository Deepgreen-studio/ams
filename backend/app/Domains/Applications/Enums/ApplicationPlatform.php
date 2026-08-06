<?php

namespace App\Domains\Applications\Enums;

enum ApplicationPlatform: string
{
    case Android = 'android';
    case Ios = 'ios';
    case Web = 'web';
    case Desktop = 'desktop';

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
            self::Android => 'Android',
            self::Ios => 'iOS',
            self::Web => 'Web',
            self::Desktop => 'Desktop',
        };
    }
}

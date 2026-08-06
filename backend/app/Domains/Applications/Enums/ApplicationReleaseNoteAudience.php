<?php

namespace App\Domains\Applications\Enums;

enum ApplicationReleaseNoteAudience: string
{
    case Public = 'public';
    case Internal = 'internal';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::Internal => 'Internal',
            self::Both => 'Both',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

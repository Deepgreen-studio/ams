<?php

namespace App\Domains\Applications\Enums;

enum ApplicationVisibility: string
{
    case Private = 'private';
    case Internal = 'internal';
    case Public = 'public';

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
            self::Private => 'Private',
            self::Internal => 'Internal',
            self::Public => 'Public',
        };
    }
}

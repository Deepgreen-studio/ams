<?php

namespace App\Domains\Customers\Enums;

enum CustomerNoteType: string
{
    case General = 'general';
    case Internal = 'internal';
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
            self::General => 'Note',
            self::Internal => 'Internal Comment',
            self::Meeting => 'Meeting Note',
        };
    }
}

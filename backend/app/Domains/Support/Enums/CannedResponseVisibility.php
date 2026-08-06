<?php

namespace App\Domains\Support\Enums;

enum CannedResponseVisibility: string
{
    case Personal = 'personal';
    case Shared = 'shared';

    public function label(): string
    {
        return match ($this) {
            self::Personal => 'Personal',
            self::Shared => 'Shared',
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

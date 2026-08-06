<?php

namespace App\Domains\Support\Enums;

enum SupportSlaEscalationLevel: string
{
    case Level1 = 'level_1';
    case Level2 = 'level_2';
    case Level3 = 'level_3';
    case Manager = 'manager';
    case Administrator = 'administrator';

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
            self::Level1 => 'Level 1',
            self::Level2 => 'Level 2',
            self::Level3 => 'Level 3',
            self::Manager => 'Manager',
            self::Administrator => 'Administrator',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Level1 => 1,
            self::Level2 => 2,
            self::Level3 => 3,
            self::Manager => 4,
            self::Administrator => 5,
        };
    }
}

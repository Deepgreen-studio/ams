<?php

namespace App\Domains\Applications\Enums;

enum ApplicationReleaseType: string
{
    case Major = 'major';
    case Minor = 'minor';
    case Patch = 'patch';
    case Hotfix = 'hotfix';
    case Rollback = 'rollback';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Major => 'Major',
            self::Minor => 'Minor',
            self::Patch => 'Patch',
            self::Hotfix => 'Hotfix',
            self::Rollback => 'Rollback',
            self::Custom => 'Custom',
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

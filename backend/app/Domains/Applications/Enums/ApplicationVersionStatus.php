<?php

namespace App\Domains\Applications\Enums;

enum ApplicationVersionStatus: string
{
    case Draft = 'draft';
    case Testing = 'testing';
    case Beta = 'beta';
    case Production = 'production';
    case Deprecated = 'deprecated';
    case Rollback = 'rollback';

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
            self::Draft => 'Draft',
            self::Testing => 'Testing',
            self::Beta => 'Beta',
            self::Production => 'Production',
            self::Deprecated => 'Deprecated',
            self::Rollback => 'Rollback',
        };
    }
}

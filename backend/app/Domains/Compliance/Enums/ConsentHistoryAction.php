<?php

namespace App\Domains\Compliance\Enums;

enum ConsentHistoryAction: string
{
    case Created = 'created';
    case Granted = 'granted';
    case Withdrawn = 'withdrawn';
    case Updated = 'updated';
    case VersionChanged = 'version_changed';
    case Restored = 'restored';

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
            self::Created => 'Created',
            self::Granted => 'Granted',
            self::Withdrawn => 'Withdrawn',
            self::Updated => 'Updated',
            self::VersionChanged => 'Version Changed',
            self::Restored => 'Restored',
        };
    }
}

<?php

namespace App\Domains\Integrations\Enums;

enum SyncConflictStrategy: string
{
    case Skip = 'skip';
    case Overwrite = 'overwrite';
    case Merge = 'merge';
    case Manual = 'manual';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

namespace App\Domains\Integrations\Enums;

enum SyncDirection: string
{
    case Import = 'import';
    case Export = 'export';
    case Bidirectional = 'bidirectional';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

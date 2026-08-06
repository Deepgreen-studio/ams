<?php

namespace App\Domains\Integrations\Enums;

enum SyncMode: string
{
    case Full = 'full';
    case Incremental = 'incremental';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

namespace App\Domains\Integrations\Enums;

enum SyncTrigger: string
{
    case Manual = 'manual';
    case Automatic = 'automatic';
    case Scheduled = 'scheduled';
    case Background = 'background';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

namespace App\Domains\Integrations\Enums;

enum DataMappingDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
    case Bidirectional = 'bidirectional';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

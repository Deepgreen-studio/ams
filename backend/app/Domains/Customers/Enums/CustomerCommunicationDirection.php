<?php

namespace App\Domains\Customers\Enums;

enum CustomerCommunicationDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
    case Internal = 'internal';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

namespace App\Domains\Customers\Enums;

enum CustomerCommunicationStatus: string
{
    case Logged = 'logged';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Failed = 'failed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

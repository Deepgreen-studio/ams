<?php

namespace App\Domains\Queue\Enums;

enum QueueJobType: string
{
    case Import = 'import';
    case Export = 'export';
    case Webhook = 'webhook';
    case Sync = 'sync';
    case Notification = 'notification';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

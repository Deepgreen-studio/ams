<?php

namespace App\Domains\Integrations\Enums;

enum WebhookDirection: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

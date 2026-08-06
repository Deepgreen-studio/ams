<?php

namespace App\Domains\Integrations\Enums;

enum WebhookStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Paused = 'paused';
    case Disabled = 'disabled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

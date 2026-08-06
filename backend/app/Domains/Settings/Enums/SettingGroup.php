<?php

namespace App\Domains\Settings\Enums;

enum SettingGroup: string
{
    case General = 'general';
    case Email = 'email';
    case Storage = 'storage';
    case Security = 'security';
    case Api = 'api';
    case Queue = 'queue';
    case Cache = 'cache';
    case Localization = 'localization';
    case Notifications = 'notifications';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

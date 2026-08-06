<?php

namespace App\Domains\Settings\Enums;

final class SettingPermission
{
    public const VIEW = 'settings.view';

    public const UPDATE = 'settings.update';

    public const MANAGE = 'settings.manage';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::UPDATE,
            self::MANAGE,
        ];
    }
}

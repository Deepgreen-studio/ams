<?php

namespace App\Domains\Settings\Enums;

final class SettingPermission
{
    public const VIEW = 'settings.view';

    public const UPDATE = 'settings.update';

    public const MANAGE = 'settings.manage';

    public const EMAIL = 'settings.email';

    public const STORAGE = 'settings.storage';

    public const SECURITY = 'settings.security';

    public const API = 'settings.api';

    public const QUEUE = 'settings.queue';

    public const MEDIA = 'settings.media';

    public const FILES = 'settings.files';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::UPDATE,
            self::MANAGE,
            self::EMAIL,
            self::STORAGE,
            self::SECURITY,
            self::API,
            self::QUEUE,
            self::MEDIA,
            self::FILES,
        ];
    }
}

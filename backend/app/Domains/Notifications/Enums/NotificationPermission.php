<?php

namespace App\Domains\Notifications\Enums;

final class NotificationPermission
{
    public const VIEW = 'notifications.view';

    public const CREATE = 'notifications.create';

    public const UPDATE = 'notifications.update';

    public const DELETE = 'notifications.delete';

    public const APPROVE = 'notifications.approve';

    public const PUBLISH = 'notifications.publish';

    public const CENTER = 'notifications.center';

    public const UNREAD = 'notifications.unread';

    public const HISTORY = 'notifications.history';

    public const PREFERENCES = 'notifications.preferences';

    public const TEMPLATES = 'notifications.templates';

    public const LOGS = 'notifications.logs';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::CREATE,
            self::UPDATE,
            self::DELETE,
            self::APPROVE,
            self::PUBLISH,
            self::CENTER,
            self::UNREAD,
            self::HISTORY,
            self::PREFERENCES,
            self::TEMPLATES,
            self::LOGS,
        ];
    }
}
